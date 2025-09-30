<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

interface Appointment {
  appointment_id: string;
  date: string;
  time: string;
  branch: string;
  services: string[];
  dentist: string;
  status: string;
  notes?: string;
  created_at: string;
  updated_at: string;
}

interface Branch {
  id: string;
  name: string;
}

interface Dentist {
  user_id: string;
  first_name: string;
  last_name: string;
}


interface Schedule {
  schedule_id: string;
  date: string;
  time: string;
}

interface Treatment {
  id: string;
  name: string;
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'My Appointments', href: '/appointments' },
];

const statusOptions = ['All Status', 'Scheduled', 'Completed', 'Cancelled'];
const selectedStatus = ref('All Status');
const searchQuery = ref('');
const rowsPerPage = ref(10);
const currentPage = ref(1);
const isLoading = ref(true);
const error = ref<string | null>(null);

const appointments = ref<Appointment[]>([]);

onMounted(async () => {
  try {
    const response = await axios.get('/dashboard/appointments', {
      headers: { 'Cache-Control': 'no-cache' },
    });
    appointments.value = response.data.appointments;
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to load appointments. Please try again later.';
    console.error('Error fetching appointments:', err);
  } finally {
    isLoading.value = false;
  }
});

// Formatters
const formatDate = (date: string): string =>
  date
    ? new Date(date).toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
      })
    : new Date().toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
      });

const formatDateTime = (dateTime: string): string =>
  dateTime
    ? new Date(dateTime).toLocaleString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
      })
    : new Date().toLocaleString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
      });

const formatTimeForDisplay = (time: string): string => {
  if (!time || !time.match(/^\d{2}:\d{2}(:\d{2})?$/)) return 'N/A';
  return new Date(`1970-01-01T${time}`).toLocaleTimeString('en-US', {
    hour: 'numeric',
    minute: '2-digit',
  });
};

// Filtering & Pagination
const filteredAppointments = computed(() => {
  let filtered = appointments.value;
  if (selectedStatus.value !== 'All Status') {
    filtered = filtered.filter(a => a.status === selectedStatus.value);
  }
  if (searchQuery.value) {
    filtered = filtered.filter(a =>
      a.services.join(', ').toLowerCase().includes(searchQuery.value.toLowerCase())
    );
  }
  return filtered.sort((a, b) => {
    const dateA = new Date(a.date).getTime();
    const dateB = new Date(b.date).getTime();
    if (dateA !== dateB) return dateA - dateB;
    const [hA, mA, sA] = a.time.split(':').map(Number);
    const [hB, mB, sB] = b.time.split(':').map(Number);
    return hA * 3600 + mA * 60 + (sA || 0) - (hB * 3600 + mB * 60 + (sB || 0));
  });
});

const paginatedAppointments = computed(() => {
  const start = (currentPage.value - 1) * rowsPerPage.value;
  return filteredAppointments.value.slice(start, start + rowsPerPage.value);
});

const totalPages = computed(() => Math.ceil(filteredAppointments.value.length / rowsPerPage.value) || 1);

function prevPage() { if (currentPage.value > 1) currentPage.value--; }
function nextPage() { if (currentPage.value < totalPages.value) currentPage.value++; }

// View Modal
const showViewModal = ref(false);
const viewAppointment = ref<Appointment | null>(null);

function openViewModal(appt: Appointment) {
  viewAppointment.value = JSON.parse(JSON.stringify(appt));
  showViewModal.value = true;
}
function closeViewModal() {
  showViewModal.value = false;
  viewAppointment.value = null;
}

// Cancel Modal
const showCancelModal = ref(false);
const cancelAppointmentData = ref<Appointment | null>(null);
const cancelReason = ref('');
const cancelIndex = ref<number | null>(null);

function openCancelModal(appt: Appointment, idx: number) {
  cancelAppointmentData.value = JSON.parse(JSON.stringify(appt));
  cancelReason.value = '';
  cancelIndex.value = idx;
  showCancelModal.value = true;
}
function closeCancelModal() {
  showCancelModal.value = false;
  cancelAppointmentData.value = null;
  cancelReason.value = '';
  cancelIndex.value = null;
}
async function confirmCancel() {
  if (cancelAppointmentData.value !== null && cancelIndex.value !== null) {
    try {
      await axios.post(`/api/appointments/${cancelAppointmentData.value.appointment_id}/cancel`, {
        reason: cancelReason.value || 'Cancelled by patient',
      });
      appointments.value[cancelIndex.value].status = 'Cancelled';
      appointments.value[cancelIndex.value].updated_at = new Date().toISOString();
      closeCancelModal();
    } catch (err: any) {
      console.error('Error cancelling appointment:', err);
      error.value = err.response?.data?.message || 'Failed to cancel appointment.';
    }
  }
}

// Reschedule Modal
const showEditModal = ref(false);
const editAppointment = ref<Appointment | null>(null);
const editIndex = ref<number | null>(null);
const branches = ref<Branch[]>([]);
const dentists = ref<Dentist[]>([]);
const schedules = ref<Schedule[]>([]);
const treatments = ref<Treatment[]>([]);
const selectedBranch = ref<string | null>(null);
const selectedDentist = ref<string | null>(null);
const selectedSchedule = ref<string | null>(null);
const selectedTreatments = ref<string[]>([]);
const rescheduleReason = ref('');
const isLoadingBranches = ref(false);
const isLoadingDentists = ref(false);
const isLoadingSchedules = ref(false);
const isLoadingTreatments = ref(false);

async function fetchBranches() {
  try {
    isLoadingBranches.value = true;
    const response = await axios.get('/fetch-branches');
    branches.value = response.data;
  } catch (err) {
    console.error('Error fetching branches:', err);
    error.value = 'Failed to load branches.';
  } finally {
    isLoadingBranches.value = false;
  }
}

async function fetchDentists(branchId: string) {
  try {
    isLoadingDentists.value = true;
    const response = await axios.get(`/appointment/branch/${branchId}/dentist`);
    dentists.value = response.data;
  } catch (err) {
    console.error('Error fetching dentists:', err);
    error.value = 'Failed to load dentists.';
  } finally {
    isLoadingDentists.value = false;
  }
}

async function fetchSchedules(branchId: string, dentistId: string) {
  try {
    isLoadingSchedules.value = true;
    const response = await axios.get(`/appointment/branch/${branchId}/dentist/schedule`, {
      params: { dentist_id: dentistId },
    });
    schedules.value = response.data.filter((s: Schedule) => s.date >= new Date().toISOString().split('T')[0]);
  } catch (err) {
    console.error('Error fetching schedules:', err);
    error.value = 'Failed to load schedules.';
  } finally {
    isLoadingSchedules.value = false;
  }
}

async function fetchTreatments() {
  try {
    isLoadingTreatments.value = true;
    const response = await axios.get('/appointment/treatments');
    treatments.value = response.data;
  } catch (err) {
    console.error('Error fetching treatments:', err);
    error.value = 'Failed to load treatments.';
  } finally {
    isLoadingTreatments.value = false;
  }
}

async function openEditModal(appt: Appointment, idx: number) {
  editAppointment.value = JSON.parse(JSON.stringify(appt));
  editIndex.value = idx;
  rescheduleReason.value = '';

  // Fetch branches and treatments
  await Promise.all([fetchBranches(), fetchTreatments()]);

  // Initialize dropdowns with original appointment values
  const branch = branches.value.find(b => b.name === appt.branch);
  selectedBranch.value = branch ? branch.id : null;
  if (branch) {
    await fetchDentists(branch.id);
    const dentist = dentists.value.find(d => d.full_name === appt.dentist);
    selectedDentist.value = dentist ? dentist.id : null;
    if (dentist) {
      await fetchSchedules(branch.id, dentist.id);
      const schedule = schedules.value.find(s => s.date === appt.date && s.time === appt.time);
      selectedSchedule.value = schedule ? schedule.schedule_id : null;
    } else {
      selectedSchedule.value = null;
    }
  } else {
    selectedDentist.value = null;
    selectedSchedule.value = null;
  }
  selectedTreatments.value = appt.services
    .map((service: string) => {
      const treatment = treatments.value.find(t => t.name === service);
      return treatment ? treatment.id : '';
    })
    .filter(id => id);

  showEditModal.value = true;
}

function closeEditModal() {
  showEditModal.value = false;
  editAppointment.value = null;
  editIndex.value = null;
  selectedBranch.value = null;
  selectedDentist.value = null;
  selectedSchedule.value = null;
  selectedTreatments.value = [];
  rescheduleReason.value = '';
  branches.value = [];
  dentists.value = [];
  schedules.value = [];
  treatments.value = [];
}

async function saveEdit() {
  if (editAppointment.value !== null && editIndex.value !== null && selectedSchedule.value && selectedTreatments.value.length > 0) {
    try {
      const payload = {
        schedule_id: selectedSchedule.value,
        treatment_ids: selectedTreatments.value,
        notes: editAppointment.value.notes || '',
        reason: rescheduleReason.value || 'Rescheduled by patient',
      };
      const response = await axios.put(`/api/appointments/${editAppointment.value.appointment_id}`, payload);
      const selectedScheduleData = schedules.value.find(s => s.schedule_id === selectedSchedule.value);
      appointments.value[editIndex.value] = {
        ...editAppointment.value,
        date: selectedScheduleData?.date || response.data.appointment.date,
        time: selectedScheduleData?.time || response.data.appointment.time,
        branch: branches.value.find(b => b.id === selectedBranch.value)?.name || response.data.appointment.branch,
        dentist: dentists.value.find(d => d.id === selectedDentist.value)?.full_name || response.data.appointment.dentist,
        services: selectedTreatments.value.map(id => {
          const treatment = treatments.value.find(t => t.id === id);
          return treatment ? treatment.name : '';
        }).filter(name => name),
        notes: payload.notes,
        updated_at: new Date().toISOString(),
      };
      closeEditModal();
    } catch (err: any) {
      console.error('Error rescheduling appointment:', err);
      error.value = err.response?.data?.message || 'Failed to reschedule appointment.';
    }
  } else {
    error.value = 'Please select a schedule and at least one treatment.';
  }
}

// Status Color
const getStatusColor = (status: string) => {
  switch (status) {
    case 'Scheduled': return 'bg-blue-100 text-blue-800';
    case 'Completed': return 'bg-green-100 text-green-800';
    case 'Cancelled': return 'bg-red-100 text-red-800';
    default: return 'bg-gray-100 text-gray-800';
  }
};
</script>

<template>
  <Head title="My Appointments" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">My Appointments</h1>
        <button
          class="bg-darkGreen-900 text-white px-4 py-2 rounded-lg hover:bg-darkGreen-800 transition-colors duration-200"
          @click="$inertia.get('/appointment')"
        >
          Book New Appointment
        </button>
      </div>

      <div v-if="isLoading" class="text-center py-4 text-gray-500">
        Loading your appointments...
      </div>
      <div v-else-if="error" class="text-center py-4 text-red-500">
        {{ error }}
      </div>
      <div v-else class="bg-white rounded-lg shadow-md p-6">
        <div class="flex flex-col sm:flex-row gap-4 mb-6">
          <div class="flex-1">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search by service..."
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
            />
          </div>
          <div class="flex gap-2">
            <select
              v-model="selectedStatus"
              class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
            >
              <option v-for="status in statusOptions" :key="status" :value="status">
                {{ status }}
              </option>
            </select>
            <select
              v-model="rowsPerPage"
              @change="currentPage = 1"
              class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
            >
              <option value="5">5 per page</option>
              <option value="10">10 per page</option>
              <option value="25">25 per page</option>
            </select>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full bg-white rounded-lg shadow overflow-hidden">
            <thead>
              <tr class="bg-[#1e4f4f] text-white">
                <th class="px-4 py-2 text-left">Schedule</th>
                <th class="px-4 py-2 text-left">Dentist</th>
                <th class="px-4 py-2 text-left">Branch</th>
                <th class="px-4 py-2 text-left">Services</th>
                <th class="px-4 py-2 text-left">Status</th>
                <th class="px-4 py-2 text-left">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="paginatedAppointments.length === 0">
                <td colspan="6" class="text-center py-8 text-gray-500 text-lg">No appointments found.</td>
              </tr>
              <tr v-for="(appointment, idx) in paginatedAppointments" :key="appointment.appointment_id" class="border-b last:border-b-0 hover:bg-gray-50">
                <td class="px-4 py-2">
                  <div class="font-medium">{{ formatDate(appointment.date) }}</div>
                  <div class="text-sm text-gray-500">{{ formatTimeForDisplay(appointment.time) }}</div>
                </td>
                <td class="px-4 py-2">{{ appointment.dentist }}</td>
                <td class="px-4 py-2">{{ appointment.branch }}</td>
                <td class="px-4 py-2">{{ appointment.services.join(', ') }}</td>
                <td class="px-4 py-2">
                  <span :class="`px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(appointment.status)}`">
                    {{ appointment.status }}
                  </span>
                </td>
                <td class="px-4 py-2">
                  <div class="flex gap-2">
                    <button
                      @click="openViewModal(appointment)"
                      class="bg-gray-600 text-white px-3 py-1 rounded hover:bg-gray-700 transition text-sm"
                    >View</button>
                    <button
                      v-if="appointment.status === 'Scheduled'"
                      @click="openEditModal(appointment, idx)"
                      class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition text-sm"
                    >Reschedule</button>
                    <button
                      v-if="appointment.status === 'Scheduled'"
                      @click="openCancelModal(appointment, idx)"
                      class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 transition text-sm"
                    >Cancel</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex flex-col sm:flex-row sm:justify-end sm:items-center mt-4 gap-4">
          <div class="flex items-center gap-2">
            <button
              @click="prevPage"
              :disabled="currentPage === 1"
              class="border border-gray-300 rounded px-3 py-1 text-lg text-gray-700 bg-white disabled:opacity-50"
            >&lt;</button>
            <span class="text-sm text-gray-700">Page {{ currentPage }} of {{ totalPages }}</span>
            <button
              @click="nextPage"
              :disabled="currentPage === totalPages || totalPages === 0"
              class="border border-gray-300 rounded px-3 py-1 text-lg text-gray-700 bg-white disabled:opacity-50"
            >&gt;</button>
          </div>
        </div>
      </div>

      <!-- View Appointment Modal -->
      <div v-if="showViewModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/20 backdrop-blur-sm">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-900">Appointment Details</h2>
            <button
              @click="closeViewModal"
              class="text-gray-400 hover:text-gray-600 text-2xl font-bold"
            >&times;</button>
          </div>
          <div v-if="viewAppointment" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Date</label>
              <p class="text-gray-900">{{ formatDate(viewAppointment.date) }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Time</label>
              <p class="text-gray-900">{{ formatTimeForDisplay(viewAppointment.time) }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Branch</label>
              <p class="text-gray-900">{{ viewAppointment.branch }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Dentist</label>
              <p class="text-gray-900">{{ viewAppointment.dentist }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Services</label>
              <p class="text-gray-900">{{ viewAppointment.services.join(', ') }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Status</label>
              <p :class="`text-sm ${getStatusColor(viewAppointment.status)}`">{{ viewAppointment.status }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Booked At</label>
              <p class="text-gray-900">{{ formatDateTime(viewAppointment.created_at) }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Last Updated</label>
              <p class="text-gray-900">{{ formatDateTime(viewAppointment.updated_at) }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Notes</label>
              <p class="text-gray-900">{{ viewAppointment.notes || 'N/A' }}</p>
            </div>
            <div class="flex justify-end pt-4">
              <button
                @click="closeViewModal"
                class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 transition-colors duration-200"
              >Close</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Cancel Appointment Modal -->
      <div v-if="showCancelModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/20 backdrop-blur-sm">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-900">Cancel Appointment</h2>
            <button
              @click="closeCancelModal"
              class="text-gray-400 hover:text-gray-600 text-2xl font-bold"
            >&times;</button>
          </div>
          <div v-if="cancelAppointmentData" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Date & Time</label>
              <p class="text-gray-900">{{ formatDate(cancelAppointmentData.date) }} at {{ formatTimeForDisplay(cancelAppointmentData.time) }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Branch</label>
              <p class="text-gray-900">{{ cancelAppointmentData.branch }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Dentist</label>
              <p class="text-gray-900">{{ cancelAppointmentData.dentist }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Services</label>
              <p class="text-gray-900">{{ cancelAppointmentData.services.join(', ') }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Cancellation</label>
              <textarea
                v-model="cancelReason"
                rows="4"
                placeholder="Enter reason for cancellation (optional)"
                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900 resize-none"
              ></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-4">
              <button
                @click="confirmCancel"
                class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition-colors duration-200"
              >Confirm Cancellation</button>
              <button
                @click="closeCancelModal"
                class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 transition-colors duration-200"
              >Close</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Reschedule Appointment Modal -->
      <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/20 backdrop-blur-sm">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-900">Reschedule Appointment</h2>
            <button
              @click="closeEditModal"
              class="text-gray-400 hover:text-gray-600 text-2xl font-bold"
            >&times;</button>
          </div>
          <div v-if="editAppointment" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Current Appointment</label>
              <p class="text-gray-900">
                {{ formatDate(editAppointment.date) }} at {{ formatTimeForDisplay(editAppointment.time) }}<br>
                {{ editAppointment.branch }} with {{ editAppointment.dentist }}<br>
                Services: {{ editAppointment.services.join(', ') }}
              </p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
              <select
                v-model="selectedBranch"
                @change="selectedDentist = null; selectedSchedule = null; selectedTreatments = editAppointment.services.map(s => treatments.value.find(t => t.name === s)?.id || '').filter(id => id); fetchDentists(selectedBranch || '')"
                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                :disabled="isLoadingBranches"
              >
                <option value="" disabled>Select a branch</option>
                <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                  {{ branch.branch_name }}
                </option>
              </select>
              <p v-if="isLoadingBranches" class="text-sm text-gray-500 mt-1">Loading branches...</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Dentist</label>
              <select
                v-model="selectedDentist"
                @change="selectedSchedule = null; fetchSchedules(selectedBranch || '', selectedDentist || '')"
                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                :disabled="!selectedBranch || isLoadingDentists"
              >
                <option value="" disabled>Select a dentist</option>
                <option v-for="dentist in dentists" :key="dentist.id" :value="dentist.id">
                  {{ dentist.first_name }} {{ dentist.last_name }}
                </option>
              </select>
              <p v-if="isLoadingDentists" class="text-sm text-gray-500 mt-1">Loading dentists...</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Schedule</label>
              <select
                v-model="selectedSchedule"
                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                :disabled="!selectedDentist || isLoadingSchedules"
              >
                <option value="" disabled>Select a schedule</option>
                <option v-for="schedule in schedules" :key="schedule.schedule_id" :value="schedule.schedule_id">
                  {{ formatDate(schedule.date) }} at {{ formatTimeForDisplay(schedule.time) }}
                </option>
              </select>
              <p v-if="isLoadingSchedules" class="text-sm text-gray-500 mt-1">Loading schedules...</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Treatments</label>
              <select
                v-model="selectedTreatments"
                multiple
                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                :disabled="isLoadingTreatments"
              >
                <option v-for="treatment in treatments" :key="treatment.id" :value="treatment.id">
                  {{ treatment.name }}
                </option>
              </select>
              <p v-if="isLoadingTreatments" class="text-sm text-gray-500 mt-1">Loading treatments...</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
              <textarea
                v-model="editAppointment.notes"
                rows="3"
                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900 resize-none"
              ></textarea>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Reschedule</label>
              <textarea
                v-model="rescheduleReason"
                rows="4"
                placeholder="Enter reason for rescheduling (optional)"
                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900 resize-none"
              ></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-4">
              <button
                @click="saveEdit"
                class="bg-darkGreen-900 text-white px-4 py-2 rounded hover:bg-darkGreen-800 transition-colors duration-200"
                :disabled="!selectedSchedule || selectedTreatments.length === 0"
              >Save Changes</button>
              <button
                @click="closeEditModal"
                class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 transition-colors duration-200"
              >Cancel</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.bg-darkGreen-900 {
  background-color: #1e4f4f;
}
</style>