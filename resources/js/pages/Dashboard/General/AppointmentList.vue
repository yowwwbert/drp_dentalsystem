<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

interface Appointment {
  appointment_id: string;
  patient_id: string;
  patient_first_name: string;
  patient_last_name: string;
  date: string;
  start_time: string;
  end_time: string;
  branch: string;
  branch_id: string;
  services: string[];
  dentist: string;
  dentist_id: string;
  status: string;
  notes?: string;
  balance?: number;
  created_at: string;
  updated_at: string;
}

interface User {
  user_id: string;
  first_name: string;
  last_name: string;
  user_type: string;
  branch_id?: string;
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Appointments', href: '/appointments' },
];

const statusOptions = ['All Status', 'Scheduled', 'Checked In', 'Completed', 'Cancelled', 'No Show'];
const selectedStatus = ref('All Status');
const searchQuery = ref('');
const rowsPerPage = ref(10);
const currentPage = ref(1);
const isLoading = ref(true);
const error = ref<string | null>(null);

const appointments = ref<Appointment[]>([]);

const page = usePage<{ auth: { user: User | null } }>();
const user = computed(() => page.props.auth.user);
const userType = computed(() => user.value?.user_type || 'User');
const userId = computed(() => user.value?.user_id || '');
const userBranchId = computed(() => user.value?.branch_id || null);
const isPatient = computed(() => userType.value === 'Patient');

onMounted(async () => {
  try {
    const params: Record<string, string> = {};
    if (userType.value === 'Patient') {
      params.patient_id = userId.value;
    } else if (userType.value === 'Dentist') {
      params.dentist_id = userId.value;
    } else if (userType.value === 'Receptionist' && userBranchId.value) {
      params.branch_id = userBranchId.value;
    }

    const response = await axios.get('/dashboard/appointments', {
      headers: { 'Cache-Control': 'no-cache' },
      params,
    });
    appointments.value = response.data.appointments;
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to load appointments. Please try again later.';
    console.error('Error fetching appointments:', err);
  } finally {
    isLoading.value = false;
  }
});

const formatTime = (time: string): string => {
  if (!time || time === 'N/A') return 'N/A';
  const [hour, minute] = time.split(':').map(Number);
  const period = hour >= 12 ? 'PM' : 'AM';
  const formattedHour = hour % 12 || 12;
  return `${formattedHour}:${minute.toString().padStart(2, '0')} ${period}`;
};

const formatDate = (date: string): string =>
  date
    ? new Date(date).toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
      })
    : 'N/A';

const formatDateTime = (dateTime: string): string =>
  dateTime
    ? new Date(dateTime).toLocaleString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
      })
    : 'N/A';

const getPatientFullName = (appointment: Appointment): string => {
  return `${appointment.patient_first_name} ${appointment.patient_last_name}`;
};

// Filtering & Pagination
const filteredAppointments = computed(() => {
  let filtered = appointments.value;
  if (selectedStatus.value !== 'All Status') {
    filtered = filtered.filter(a => a.status === selectedStatus.value);
  }
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(a =>
      isPatient.value
        ? a.services.join(', ').toLowerCase().includes(query)
        : a.patient_last_name.toLowerCase().includes(query)
    );
  }
  return filtered.sort((a, b) => {
    const dateA = new Date(a.date).getTime();
    const dateB = new Date(b.date).getTime();
    if (dateA !== dateB) return dateA - dateB;
    const timeA = a.start_time || '00:00:00';
    const timeB = b.start_time || '00:00:00';
    const [hA, mA, sA] = timeA.split(':').map(Number);
    const [hB, mB, sB] = timeB.split(':').map(Number);
    return hA * 3600 + mA * 60 + (sA || 0) - (hB * 3600 + mB * 60 + (sB || 0));
  });
});

const paginatedAppointments = computed(() => {
  const start = (currentPage.value - 1) * rowsPerPage.value;
  return filteredAppointments.value.slice(start, start + rowsPerPage.value);
});

const totalPages = computed(() => Math.ceil(filteredAppointments.value.length / rowsPerPage.value) || 1);

function prevPage() {
  if (currentPage.value > 1) currentPage.value--;
}
function nextPage() {
  if (currentPage.value < totalPages.value) currentPage.value++;
}

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

// Navigate to Manage Appointment page
function manageAppointment(appointmentId: string) {
  router.visit(`/appointments/${appointmentId}/manage`);
}

// Status Color
const getStatusColor = (status: string) => {
  switch (status) {
    case 'Scheduled':
      return 'bg-blue-100 text-blue-800';
    case 'Checked In':
      return 'bg-purple-100 text-purple-800';
    case 'Completed':
      return 'bg-green-100 text-green-800';
    case 'Cancelled':
      return 'bg-red-100 text-red-800';
    case 'No Show':
      return 'bg-orange-100 text-orange-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
};
</script>

<template>
  <Head :title="isPatient ? 'My Appointments' : 'Appointments'" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">{{ isPatient ? 'My Appointments' : 'Appointments' }}</h1>
        <button
          v-if="isPatient"
          class="bg-darkGreen-900 text-white px-4 py-2 rounded-lg hover:bg-darkGreen-800 transition-colors duration-200"
          @click="router.visit('/appointment')"
        >
          Book New Appointment
        </button>
      </div>

      <div v-if="isLoading" class="text-center py-4 text-gray-500">
        Loading {{ isPatient ? 'your' : '' }} appointments...
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
              :placeholder="isPatient ? 'Search by service...' : 'Search by patient name...'"
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
              <option :value="5">5 per page</option>
              <option :value="10">10 per page</option>
              <option :value="25">25 per page</option>
            </select>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full bg-white rounded-lg shadow overflow-hidden">
            <thead>
              <tr class="bg-[#1e4f4f] text-white">
                <th class="px-4 py-2 text-left">{{ isPatient ? 'Schedule' : 'Patient' }}</th>
                <th class="px-4 py-2 text-left">{{ isPatient ? 'Dentist' : 'Schedule' }}</th>
                <th class="px-4 py-2 text-left">{{ isPatient ? 'Branch' : 'Dentist' }}</th>
                <th class="px-4 py-2 text-left">{{ isPatient ? 'Services' : 'Branch' }}</th>
                <th v-if="!isPatient" class="px-4 py-2 text-left">Services</th>
                <th class="px-4 py-2 text-left">Status</th>
                <th class="px-4 py-2 text-left">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="paginatedAppointments.length === 0">
                <td :colspan="isPatient ? 6 : 7" class="text-center py-8 text-gray-500 text-lg">
                  No appointments found.
                </td>
              </tr>
              <tr
                v-for="appointment in paginatedAppointments"
                :key="appointment.appointment_id"
                class="border-b last:border-b-0 hover:bg-gray-50"
              >
                <td class="px-4 py-2">
                  <div v-if="isPatient">
                    <div class="font-medium text-darkGreen-900">{{ formatDate(appointment.date) }}</div>
                    <div class="text-sm text-gray-500">
                      {{ formatTime(appointment.start_time) }} - {{ formatTime(appointment.end_time) }}
                    </div>
                  </div>
                  <div v-if="!isPatient" class="font-medium">
                    {{ appointment.patient_last_name }},
                    <div class="text-sm text-gray-500 font-normal">{{ appointment.patient_first_name }}</div>
                  </div>
                </td>
                <td class="px-4 py-2">
                  <div v-if="isPatient">{{ appointment.dentist }}</div>
                  <div v-if="!isPatient">
                    <div class="font-medium text-darkGreen-900">{{ formatDate(appointment.date) }}</div>
                    <div class="text-sm text-gray-500">
                      {{ formatTime(appointment.start_time) }} - {{ formatTime(appointment.end_time) }}
                    </div>
                  </div>
                </td>
                <td class="px-4 py-2">
                  <div v-if="isPatient">{{ appointment.branch }}</div>
                  <div v-if="!isPatient">{{ appointment.dentist }}</div>
                </td>
                <td class="px-4 py-2">
                  <div v-if="isPatient">{{ appointment.services.join(', ') }}</div>
                  <div v-if="!isPatient">{{ appointment.branch }}</div>
                </td>
                <td v-if="!isPatient" class="px-4 py-2">{{ appointment.services.join(', ') }}</td>
                <td class="px-4 py-2">
                  <span
                    :class="`px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(appointment.status)}`"
                  >
                    {{ appointment.status }}
                  </span>
                </td>
                <td class="px-4 py-2">
                  <div class="flex gap-2">
                    <button
                      @click="openViewModal(appointment)"
                      class="bg-gray-600 text-white px-3 py-1 rounded hover:bg-gray-700 transition text-sm"
                    >
                      View
                    </button>
                    <button
                      @click="manageAppointment(appointment.appointment_id)"
                      class="bg-darkGreen-900 text-white px-3 py-1 rounded hover:bg-darkGreen-800 transition text-sm"
                    >
                      Manage
                    </button>
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
            >
              &lt;
            </button>
            <span class="text-sm text-gray-700">Page {{ currentPage }} of {{ totalPages }}</span>
            <button
              @click="nextPage"
              :disabled="currentPage === totalPages || totalPages === 0"
              class="border border-gray-300 rounded px-3 py-1 text-lg text-gray-700 bg-white disabled:opacity-50"
            >
              &gt;
            </button>
          </div>
        </div>
      </div>
      <div
        v-if="showViewModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/20 backdrop-blur-sm"
      >
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-900">Appointment Details</h2>
            <button @click="closeViewModal" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">
              &times;
            </button>
          </div>
          <div v-if="viewAppointment" class="space-y-4">
            <div v-if="!isPatient">
              <label class="block text-sm font-medium text-gray-700">Patient</label>
              <p class="text-gray-900">{{ getPatientFullName(viewAppointment) }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Date</label>
              <p class="text-gray-900">{{ formatDate(viewAppointment.date) }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Time</label>
              <p class="text-gray-900">
                {{ formatTime(viewAppointment.start_time) }} - {{ formatTime(viewAppointment.end_time) }}
              </p>
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
              <span
                :class="`inline-block px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(viewAppointment.status)}`"
              >
                {{ viewAppointment.status }}
              </span>
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
              >
                Close
              </button>
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

.text-darkGreen-900 {
  color: #1e4f4f;
}

.bg-darkGreen-800 {
  background-color: #2d5f5c;
}
</style>