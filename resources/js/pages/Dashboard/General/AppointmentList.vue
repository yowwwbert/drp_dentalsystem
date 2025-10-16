<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { Calendar, X, User, CalendarDays, Clock, MapPin, Stethoscope, Activity, ClipboardList, FileText, CalendarPlus, RefreshCw, UserCheck } from 'lucide-vue-next';

interface Appointment {
  appointment_id: string;
  patient_id: string;
  patient_first_name: string;
  patient_last_name: string;
  patient_email: string;
  patient_phone: string;
  balance: number;
  dentist_id: string;
  dentist_first_name: string;
  dentist_last_name: string;
  dentist_email: string;
  branch_id: string;
  branch_name: string;
  branch_address: string;
  schedule_id: string;
  date: string;
  start_time: string;
  end_time: string;
  services: string[];
  status: string;
  reschedule_count: number;
  status_changed_by: {
    user_id: string;
    first_name: string;
    last_name: string;
    user_type: string;
  };
  reason_for_status_change: string;
  billing_id: string | null;
  notes: string;
  created_at: string;
  updated_at: string;
  created_by: string;
  updated_by: string;
  treatments: {
    treatment_id: string;
    treatment_name: string;
    description: string;
    cost: number;
    duration: string;
  }[];
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
  // Extract status from URL query
  const url = new URL(window.location.href);
  const statusParam = url.searchParams.get('status');
  if (statusParam && statusOptions.includes(statusParam)) {
    selectedStatus.value = statusParam;
  }

  try {
    const params: Record<string, string> = {};
    if (userType.value === 'Patient') {
      params.patient_id = userId.value;
    } else if (userType.value === 'Dentist') {
      params.dentist_id = userId.value;
    } else if (userType.value === 'Staff' && userBranchId.value) {
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

const getStatusChangedByFullName = (appointment: Appointment): string => {
  return `${appointment.status_changed_by.first_name} ${appointment.status_changed_by.last_name} (${appointment.status_changed_by.user_type})`;
};

const getReasonLabel = (appointment: Appointment): string => {
  if (appointment.status === 'Cancelled') {
    return 'Reason for Cancellation';
  } else if (appointment.reschedule_count > 0) {
    return 'Reason for Reschedule';
  }
  return 'Reason for Status Change';
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
    <th class="px-4 py-2 text-left">{{ isPatient ? 'Status' : 'Branch' }}</th>
    <th class="px-4 py-2 text-left">{{ isPatient ? 'Actions' : 'Status' }}</th>
    <th v-if="!isPatient" class="px-4 py-2 text-left">Actions</th>
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
                  <div v-if="isPatient">{{ appointment.dentist_last_name }}, {{ appointment.dentist_first_name }}</div>
                  <div v-if="!isPatient">
                    <div class="font-medium text-darkGreen-900">{{ formatDate(appointment.date) }}</div>
                    <div class="text-sm text-gray-500">
                      {{ formatTime(appointment.start_time) }} - {{ formatTime(appointment.end_time) }}
                    </div>
                  </div>
                </td>
                <td class="px-4 py-2">
                  <div v-if="isPatient">{{ appointment.branch_name }}</div>
                  <div v-if="!isPatient">{{ appointment.dentist_last_name }}, {{ appointment.dentist_first_name }}</div>
                </td>
                <td class="px-4 py-2">
                  <div v-if="isPatient">{{ appointment.services.join(', ') }}</div>
                  <div v-if="!isPatient">{{ appointment.branch_name }}</div>
                </td>
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
        @click.self="closeViewModal"
      >
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-hidden flex flex-col">
          <!-- Header -->
          <div class="bg-[#1e4f4f] text-white px-6 py-5 flex justify-between items-center">
            <div class="flex items-center gap-3">
              <Calendar class="w-6 h-6" />
              <h2 class="text-xl font-bold">Appointment Details</h2>
            </div>
            <button 
              @click="closeViewModal" 
              class="text-white/80 hover:text-white hover:bg-white/10 rounded-full p-1 transition-all duration-200"
            >
              <X class="w-6 h-6" />
            </button>
          </div>

          <!-- Content -->
          <div v-if="viewAppointment" class="overflow-y-auto p-6 space-y-6">
            <!-- Patient Info (if not patient) -->
            <div v-if="!isPatient" class="bg-gray-50 rounded-lg p-4">
              <div class="flex items-start gap-2">
                <User class="w-4 h-4 text-gray-600 mt-0.5" />
                <div>
                  <label class="text-sm font-medium text-gray-600">Patient</label>
                  <p class="text-gray-900 font-medium">{{ getPatientFullName(viewAppointment) }}</p>
                </div>
              </div>
            </div>

            <!-- Main Grid -->
            <div class="grid grid-cols-2 gap-4">
              <!-- Date -->
              <div class="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition-colors">
                <div class="flex items-center gap-2 mb-2">
                  <CalendarDays class="w-4 h-4 text-gray-600" />
                  <label class="text-sm font-medium text-gray-600">Date</label>
                </div>
                <p class="text-gray-900 font-medium">{{ formatDate(viewAppointment.date) }}</p>
              </div>
              
              <!-- Time -->
              <div class="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition-colors">
                <div class="flex items-center gap-2 mb-2">
                  <Clock class="w-4 h-4 text-gray-600" />
                  <label class="text-sm font-medium text-gray-600">Time</label>
                </div>
                <p class="text-gray-900 font-medium">
                  {{ formatTime(viewAppointment.start_time) }} - {{ formatTime(viewAppointment.end_time) }}
                </p>
              </div>

              <!-- Branch -->
              <div class="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition-colors">
                <div class="flex items-center gap-2 mb-2">
                  <MapPin class="w-4 h-4 text-gray-600" />
                  <label class="text-sm font-medium text-gray-600">Branch</label>
                </div>
                <p class="text-gray-900 font-medium">{{ viewAppointment.branch_name }}</p>
              </div>

              <!-- Dentist -->
              <div class="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition-colors">
                <div class="flex items-center gap-2 mb-2">
                  <Stethoscope class="w-4 h-4 text-gray-600" />
                  <label class="text-sm font-medium text-gray-600">Dentist</label>
                </div>
                <p class="text-gray-900 font-medium">{{ viewAppointment.dentist_last_name }}, {{ viewAppointment.dentist_first_name }}</p>
              </div>
            </div>

            <!-- Status -->
            <div class="bg-gray-50 rounded-lg p-4">
              <div class="flex items-center gap-2 mb-2">
                <Activity class="w-4 h-4 text-gray-600" />
                <label class="text-sm font-medium text-gray-600">Status</label>
              </div>
              <span
                :class="`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium ${getStatusColor(viewAppointment.status)}`"
              >
                <div class="w-2 h-2 rounded-full bg-current"></div>
                {{ viewAppointment.status }}
              </span>
            </div>

            <!-- Reschedule Count -->
            
            <!-- Status Changed By -->
            <div class="bg-gray-50 rounded-lg p-4">
              <div class="flex items-center gap-2 mb-2">
                <UserCheck class="w-4 h-4 text-gray-600" />
                <label class="text-sm font-medium text-gray-600">Status Changed By</label>
              </div>
              <p class="text-gray-900 font-medium">{{ getStatusChangedByFullName(viewAppointment) }}</p>
            </div>

            <!-- Reason for Status Change -->
            <div v-if="viewAppointment.reason_for_status_change !== 'N/A'" class="bg-gray-50 rounded-lg p-4">
              <div class="flex items-center gap-2 mb-2">
                <FileText class="w-4 h-4 text-gray-600" />
                <label class="text-sm font-medium text-gray-600">{{ getReasonLabel(viewAppointment) }}</label>
              </div>
              <p class="text-gray-900 font-medium">{{ viewAppointment.reason_for_status_change }}</p>
            </div>

            <div v-if="viewAppointment.reschedule_count > 0" class="bg-gray-50 rounded-lg p-4">
              <div class="flex items-center gap-2 mb-2">
                <RefreshCw class="w-4 h-4 text-gray-600" />
                <label class="text-sm font-medium text-gray-600">Reschedule Count</label>
              </div>
              <p class="text-gray-900 font-medium">{{ viewAppointment.reschedule_count }}</p>
            </div>


            <!-- Services -->
            <div class="bg-gray-50 rounded-lg p-4">
              <div class="flex items-center gap-2 mb-2">
                <ClipboardList class="w-4 h-4 text-gray-600" />
                <label class="text-sm font-medium text-gray-600">Services</label>
              </div>
              <div class="flex flex-wrap gap-2">
                <span 
                  v-for="service in viewAppointment.services" 
                  :key="service"
                  class="bg-white px-3 py-1.5 rounded-full text-sm text-gray-700 border border-gray-200"
                >
                  {{ service }}
                </span>
              </div>
            </div>

            <!-- Notes -->
            <div v-if="viewAppointment.notes && viewAppointment.notes !== 'N/A'" class="bg-amber-50 border-l-4 border-amber-400 rounded-lg p-4">
              <div class="flex items-center gap-2 mb-2">
                <FileText class="w-4 h-4 text-amber-700" />
                <label class="text-sm font-medium text-amber-900">Notes</label>
              </div>
              <p class="text-gray-700 leading-relaxed">{{ viewAppointment.notes }}</p>
            </div>

            <!-- Timestamps -->
            <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-200">
              <div class="flex items-start gap-2">
                <CalendarPlus class="w-4 h-4 text-gray-400 mt-0.5" />
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-0.5">Booked At</label>
                  <p class="text-sm text-gray-700">{{ formatDateTime(viewAppointment.created_at) }}</p>
                </div>
              </div>

              <div class="flex items-start gap-2">
                <RefreshCw class="w-4 h-4 text-gray-400 mt-0.5" />
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-0.5">Last Updated</label>
                  <p class="text-sm text-gray-700">{{ formatDateTime(viewAppointment.updated_at) }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="bg-gray-50 px-6 py-4 flex justify-end border-t border-gray-200">
            <button
              @click="closeViewModal"
              class="bg-gray-600 text-white px-6 py-2.5 rounded-lg hover:bg-gray-700 transition-all duration-200 font-medium shadow-sm hover:shadow"
            >
              Close
            </button>
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