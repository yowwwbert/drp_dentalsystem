```vue
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
  dentist: string;
  dentist_id: string;
  status: string;
  notes?: string;
  balance?: number;
  reschedule_count: number;
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

interface Branch {
  branch_id: string;
  branch_name: string;
}

interface Dentist {
  user_id: string;
  first_name: string;
  last_name: string;
}

interface Schedule {
  schedule_id: string;
  schedule_date: string;
  start_time: string;
  end_time: string;
}

interface Treatment {
  treatment_id: string;
  treatment_name: string;
}

const props = defineProps<{
  appointmentId: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Appointments', href: '/dashboard/staff/appointments/AppointmentList' },
  { title: 'Manage Appointment', href: '#' },
];

const page = usePage<{ auth: { user: User | null } }>();
const user = computed(() => page.props.auth.user);
const userType = computed(() => user.value?.user_type || 'User');
const isPatient = computed(() => userType.value === 'Patient');

const isLoading = ref(true);
const error = ref<string | null>(null);
const successMessage = ref<string | null>(null);
const appointment = ref<Appointment | null>(null);

// View states
const showRescheduleForm = ref(false);
const showCancelModal = ref(false);
const showStatusModal = ref(false);
const showRescheduleConfirmModal = ref(false);

// Reschedule State
const rescheduleStep = ref<'branch' | 'dentist' | 'schedule' | 'confirm'>('branch');
const branches = ref<Branch[]>([]);
const dentists = ref<Dentist[]>([]);
const schedules = ref<Schedule[]>([]);
const treatments = ref<Treatment[]>([]);
const selectedBranch = ref<string | null>(null);
const selectedDentist = ref<string | null>(null);
const selectedSchedule = ref<string | null>(null);
const selectedTreatments = ref<string[]>([]);
const rescheduleNotes = ref('');
const rescheduleReason = ref('');
const isLoadingBranches = ref(false);
const isLoadingDentists = ref(false);
const isLoadingSchedules = ref(false);
const isLoadingTreatments = ref(false);

// Pagination for schedules
const currentPage = ref(1);
const itemsPerPage = 6;

// Cancel State
const cancelReason = ref('');

// Status State
const selectedStatus = ref('');
const statusReason = ref('');
const isProcessing = ref(false);

const statusOptions = ['Scheduled', 'Checked In', 'Completed', 'Cancelled', 'No Show'];

onMounted(async () => {
  await fetchAppointment();
  if (!isPatient.value && appointment.value) {
    selectedStatus.value = appointment.value.status || 'Scheduled';
  }
});

async function fetchAppointment() {
  try {
    isLoading.value = true;
    const response = await axios.get(`/api/appointments/${props.appointmentId}`);
    appointment.value = response.data;
    rescheduleNotes.value = appointment.value?.notes || '';
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to load appointment.';
    console.error('Error fetching appointment:', err);
  } finally {
    isLoading.value = false;
  }
}

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
    schedules.value = response.data.filter(
      (s: Schedule) => s.schedule_date >= new Date().toISOString().split('T')[0]
    );
  } catch (err) {
    console.error('Error fetching schedules:', err);
    error.value = 'Failed to load schedules.';
  } finally {
    isLoadingSchedules.value = false;
  }
}

const groupedSchedules = computed(() => {
  const groups: { [key: string]: Schedule[] } = {};
  schedules.value.forEach((schedule) => {
    const date = schedule.schedule_date;
    if (!groups[date]) {
      groups[date] = [];
    }
    groups[date].push(schedule);
  });
  
  Object.keys(groups).forEach((date) => {
    groups[date].sort((a, b) => {
      const timeA = a.start_time.split(':').map(Number);
      const timeB = b.start_time.split(':').map(Number);
      return (timeA[0] * 60 + timeA[1]) - (timeB[0] * 60 + timeB[1]);
    });
  });
  
  return groups;
});

const groupedScheduleEntries = computed(() => {
  return Object.entries(groupedSchedules.value);
});

const totalPages = computed(() => {
  return Math.ceil(groupedScheduleEntries.value.length / itemsPerPage);
});

const paginatedSchedules = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  const end = start + itemsPerPage;
  return groupedScheduleEntries.value.slice(start, end);
});

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

async function startReschedule() {
  showRescheduleForm.value = true;
  rescheduleStep.value = 'branch';
  selectedBranch.value = null;
  selectedDentist.value = null;
  selectedSchedule.value = null;
  selectedTreatments.value = [];
  rescheduleReason.value = '';
  await fetchBranches();
}

async function selectBranch(branchId: string) {
  selectedBranch.value = branchId;
  selectedDentist.value = null;
  selectedSchedule.value = null;
  dentists.value = [];
  schedules.value = [];
  await Promise.all([fetchDentists(branchId), fetchTreatments()]);
}

function confirmBranchSelection() {
  if (selectedBranch.value) {
    rescheduleStep.value = 'dentist';
  }
}

async function handleDentistChange() {
  selectedSchedule.value = null;
  schedules.value = [];
  currentPage.value = 1;
  if (selectedBranch.value && selectedDentist.value && selectedTreatments.value.length > 0) {
    await fetchSchedules(selectedBranch.value, selectedDentist.value);
  }
}

function confirmDentistAndTreatments() {
  if (selectedDentist.value && selectedTreatments.value.length > 0 && selectedBranch.value) {
    fetchSchedules(selectedBranch.value, selectedDentist.value);
    rescheduleStep.value = 'schedule';
  }
}

function nextPage() {
  if (currentPage.value < totalPages.value) {
    currentPage.value++;
  }
}

function prevPage() {
  if (currentPage.value > 1) {
    currentPage.value--;
  }
}

function goToPage(page: number) {
  currentPage.value = page;
}

async function selectSchedule(scheduleId: string) {
  selectedSchedule.value = scheduleId;
}

function confirmScheduleSelection() {
  if (selectedSchedule.value) {
    rescheduleStep.value = 'confirm';
  }
}

function openRescheduleConfirmModal() {
  showRescheduleConfirmModal.value = true;
}

async function submitReschedule() {
  if (!selectedSchedule.value || selectedTreatments.value.length === 0) {
    error.value = 'Please select a schedule and at least one treatment.';
    return;
  }

  if (!rescheduleReason.value.trim()) {
    error.value = 'Please provide a reason for rescheduling.';
    return;
  }

  try {
    isProcessing.value = true;
    error.value = null;
    const payload = {
      schedule_id: selectedSchedule.value,
      treatment_ids: selectedTreatments.value,
      notes: rescheduleNotes.value || '',
      reason: rescheduleReason.value,
    };
    
    await axios.put(`/api/appointments/${props.appointmentId}`, payload);
    successMessage.value = 'Appointment rescheduled successfully!';
    showRescheduleForm.value = false;
    showRescheduleConfirmModal.value = false;
    await fetchAppointment(); // Refresh appointment data
  } catch (err: any) {
    console.error('Error rescheduling appointment:', err);
    error.value = err.response?.data?.message || 'Failed to reschedule appointment.';
  } finally {
    isProcessing.value = false;
  }
}

function openCancelModal() {
  showCancelModal.value = true;
  cancelReason.value = '';
  error.value = null;
}

function closeCancelModal() {
  showCancelModal.value = false;
  cancelReason.value = '';
}

async function submitCancel() {
  if (!cancelReason.value.trim()) {
    error.value = 'Please provide a reason for cancellation.';
    return;
  }

  try {
    isProcessing.value = true;
    error.value = null;
    await axios.post(`/api/appointments/${props.appointmentId}/cancel`, {
      reason: cancelReason.value,
    });
    
    successMessage.value = 'Appointment cancelled successfully!';
    showCancelModal.value = false;
    await fetchAppointment(); // Refresh appointment data
  } catch (err: any) {
    console.error('Error cancelling appointment:', err);
    error.value = err.response?.data?.message || 'Failed to cancel appointment.';
  } finally {
    isProcessing.value = false;
  }
}

function openStatusModal() {
  if (!appointment.value) return;
  showStatusModal.value = true;
  selectedStatus.value = appointment.value.status || 'Scheduled';
  statusReason.value = ''; // Reset reason
  error.value = null;
}

function closeStatusModal() {
  showStatusModal.value = false;
  statusReason.value = '';
}

async function submitStatusUpdate() {
  if (!selectedStatus.value) {
    error.value = 'Please select a status.';
    return;
  }

  if ((selectedStatus.value === 'Cancelled' || selectedStatus.value === 'No Show') && !statusReason.value.trim()) {
    error.value = 'Please provide a reason for this status change.';
    return;
  }

  try {
    isProcessing.value = true;
    error.value = null;
    const payload = {
      status: selectedStatus.value,
      reason: statusReason.value || undefined,
    };
    
    await axios.put(`/api/appointments/${props.appointmentId}/status`, payload);
    
    successMessage.value = 'Appointment status updated successfully!';
    showStatusModal.value = false;
    statusReason.value = '';
    await fetchAppointment(); // Refresh appointment data
  } catch (err: any) {
    console.error('Error updating status:', err);
    error.value = err.response?.data?.message || 'Failed to update status.';
  } finally {
    isProcessing.value = false;
  }
}

function closeRescheduleForm() {
  showRescheduleForm.value = false;
  error.value = null;
}

function toggleTreatment(treatmentId: string) {
  const index = selectedTreatments.value.indexOf(treatmentId);
  if (index > -1) {
    selectedTreatments.value.splice(index, 1);
  } else {
    selectedTreatments.value.push(treatmentId);
  }
}

function goBackReschedule() {
  if (rescheduleStep.value === 'branch') {
    closeRescheduleForm();
  } else if (rescheduleStep.value === 'dentist') {
    rescheduleStep.value = 'branch';
  } else if (rescheduleStep.value === 'schedule') {
    rescheduleStep.value = 'dentist';
  } else if (rescheduleStep.value === 'confirm') {
    rescheduleStep.value = 'schedule';
  }
}

const formatDateWithDay = (date: string): string =>
  date
    ? new Date(date).toLocaleDateString('en-US', {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
      })
    : 'N/A';

const formatTime = (time: string): string => {
  if (!time || time === 'N/A') return 'N/A';
  const [hour, minute] = time.split(':').map(Number);
  const period = hour >= 12 ? 'PM' : 'AM';
  const formattedHour = hour % 12 || 12;
  return `${formattedHour}:${minute.toString().padStart(2, '0')} ${period}`;
};

const formatDateOnly = (date: string): string =>
  date
    ? new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
      })
    : 'N/A';

const formatTimeRange = (startTime: string, endTime: string): string => {
  return `${formatTime(startTime)} - ${formatTime(endTime)}`;
};

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

const canReschedule = computed(() => {
  if (!appointment.value) return false;
  const statusAllowed = appointment.value.status !== 'Completed' && appointment.value.status !== 'Cancelled';
  const rescheduleCountAllowed = (appointment.value.reschedule_count || 0) < 3;
  return statusAllowed && rescheduleCountAllowed;
});

const canCancel = computed(() => {
  return appointment.value?.status !== 'Completed' && appointment.value?.status !== 'Cancelled';
});
</script>

<template>
  <Head title="Manage Appointment" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">Manage Appointment</h1>
        <button
          @click="router.visit('/dashboard/staff/appointments/AppointmentList')"
          class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors duration-200"
        >
          Back to Appointments
        </button>
      </div>

      <!-- Error/Success Messages -->
      <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
        <span class="block md:inline">{{ error }}</span>
        <button @click="error = null" class="absolute top-0 bottom-0 right-0 px-4 py-3">
          <span class="text-2xl">&times;</span>
        </button>
      </div>
      <div v-if="successMessage" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
        <span class="block md:inline">{{ successMessage }}</span>
      </div>

      <div v-if="isLoading" class="text-center py-8">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-darkGreen-900"></div>
        <p class="mt-2 text-gray-500">Loading appointment details...</p>
      </div>

      <div v-else-if="appointment" class="space-y-6">
        <!-- Appointment Details -->
        <div v-if="!showRescheduleForm" class="bg-white rounded-lg shadow-md p-6">
          <div class="p-6 bg-gradient-to-r from-gray-50 to-gray-100 rounded-lg border border-gray-200 mb-6">
            <h2 class="text-xl font-bold mb-4 text-gray-900">Appointment Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <div v-if="!isPatient" class="bg-white p-3 rounded shadow-sm">
                <span class="text-md font-medium text-darkGreen-900 uppercase ">Patient</span>
                <p class="text-base font-semibold text-gray-900 mt-1">
                  {{ appointment.patient_first_name }} {{ appointment.patient_last_name }}
                </p>
                <p class="text-md text-gray-500 mt-1">ID: {{ appointment.patient_id }}</p>
              </div>
              <div v-if="!isPatient" class="bg-white p-3 rounded shadow-sm">
                <span class="text-md font-medium text-darkGreen-900 uppercase ">Appointment ID</span>
                <p class="text-md text-gray-700 mt-1">{{ appointment.appointment_id }}</p>
              </div>
              <div class="bg-white p-3 rounded shadow-sm">
                <span class="text-md font-medium text-darkGreen-900 uppercase ">Schedule</span>
                <p class="text-base font-semibold text-gray-900 mt-1">{{ formatDateWithDay(appointment.date) }}</p>
                <p class="text-base font-normal text-gray-500 mt-1">
                  {{ formatTime(appointment.start_time) }} - {{ formatTime(appointment.end_time) }}
                </p>
              </div>
              <div class="bg-white p-3 rounded shadow-sm">
                <span class="text-md font-medium text-darkGreen-900 uppercase ">Status</span>
                <div class="mt-1">
                  <span :class="`inline-block px-3 py-1 rounded-full text-md font-semibold ${getStatusColor(appointment.status)}`">
                    {{ appointment.status }}
                  </span>
                </div>
              </div>
              <div class="bg-white p-3 rounded shadow-sm">
                <span class="text-md font-medium text-darkGreen-900 uppercase ">Branch</span>
                <p class="text-base font-semibold text-gray-900 mt-1">{{ appointment.branch }}</p>
              </div>
              <div class="bg-white p-3 rounded shadow-sm">
                <span class="text-md font-medium text-darkGreen-900 uppercase ">Dentist</span>
                <p class="text-base font-semibold text-gray-900 mt-1">{{ appointment.dentist }}</p>
              </div>
              <div v-if="appointment.notes" class="bg-white p-3 rounded shadow-sm md:col-span-2 lg:col-span-3">
                <span class="text-md font-medium text-darkGreen-900 uppercase ">Notes</span>
                <p class="text-md text-gray-700 mt-1">{{ appointment.notes }}</p>
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div>
            <h3 class="text-lg font-semibold text-gray-900 mb-3">Actions</h3>
            <div class="flex flex-wrap gap-3">
              <button
                v-if="canReschedule"
                @click="startReschedule"
                class="bg-darkGreen-900 text-white px-4 py-2 rounded-lg hover:bg-darkGreen-800 transition-colors text-md font-medium"
              >
                Reschedule Appointment
              </button>

              <button
                v-if="!canReschedule && appointment.reschedule_count >= 3"
                disabled
                class="bg-gray-400 text-gray-600 px-4 py-2 rounded-lg cursor-not-allowed text-md font-medium"
                title="Maximum reschedule limit reached (3/3)"
              >
                Reschedule Limit Reached
              </button>

              <button
                v-if="canCancel"
                @click="openCancelModal"
                class="bg-white text-red-600 border-2 border-red-600 px-4 py-2 rounded-lg hover:bg-red-50 transition-colors text-md font-medium"
              >
                Cancel Appointment
              </button>

              <button
                v-if="!isPatient"
                @click="openStatusModal"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-md font-medium"
              >
                Update Status
              </button>
            </div>
          </div>
        </div>

        <!-- Reschedule Form -->
        <div v-if="showRescheduleForm" class="bg-white rounded-lg shadow-md p-6">
          <h2 class="text-xl font-bold mb-6 text-gray-900">Reschedule Appointment</h2>

          <!-- Step 1: Select Branch -->
          <div v-if="rescheduleStep === 'branch'" class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-700">Step 1: Select a Branch</h3>
            <div v-if="isLoadingBranches" class="text-center py-8">
              <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-darkGreen-900"></div>
              <p class="mt-2 text-gray-500">Loading branches...</p>
            </div>
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <button
                v-for="branch in branches"
                :key="branch.branch_id"
                @click="selectBranch(branch.branch_id)"
                class="rounded-lg p-4 text-left transition-all"
                :class="selectedBranch === branch.branch_id 
                  ? 'bg-darkGreen-900 text-white' 
                  : 'bg-white text-gray-900 border border-gray-300 hover:bg-gray-50'"
              >
                <h4 class="text-base font-semibold">{{ branch.branch_name }}</h4>
              </button>
            </div>
            <div class="flex justify-between pt-4">
              <button
                @click="closeRescheduleForm"
                class="bg-white text-darkGreen-900 border-2 border-darkGreen-900 px-4 py-2 rounded-lg hover:bg-darkGreen-50 transition-colors text-md font-medium"
              >
                Cancel
              </button>
              <button
                @click="confirmBranchSelection"
                :disabled="!selectedBranch"
                class="bg-darkGreen-900 text-white px-4 py-2 rounded-lg hover:bg-darkGreen-800 transition-colors text-md disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Next
              </button>
            </div>
          </div>

          <!-- Step 2: Select Dentist and Treatments -->
          <div v-if="rescheduleStep === 'dentist'" class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-700">Step 2: Select Dentist and Treatments</h3>
            
            <!-- Selection Summary -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
              <p class="text-md font-medium text-green-900 mb-2">Your Selection:</p>
              <div class="text-md text-green-800">
                <p><strong>Branch:</strong> {{ branches.find(b => b.branch_id === selectedBranch)?.branch_name }}</p>
              </div>
            </div>

            <div v-if="isLoadingDentists || isLoadingTreatments" class="text-center py-8">
              <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-darkGreen-900"></div>
              <p class="mt-2 text-gray-500">Loading...</p>
            </div>
            <div v-else>
              <div class="mb-6">
                <label class="block text-md font-medium text-gray-700 mb-3">Select Dentist *</label>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                  <button
                    v-for="dentist in dentists"
                    :key="dentist.user_id"
                    @click="selectedDentist = dentist.user_id"
                    class="rounded-lg p-4 text-left transition-all"
                    :class="selectedDentist === dentist.user_id 
                      ? 'bg-darkGreen-900' 
                      : 'bg-white border border-gray-300 hover:bg-gray-50'"
                  >
                    <div class="flex items-center gap-3">
                      <div 
                        class="w-12 h-12 rounded-full flex items-center justify-center font-semibold text-lg"
                        :class="selectedDentist === dentist.user_id 
                          ? 'bg-white text-darkGreen-900' 
                          : 'bg-darkGreen-900 text-white'"
                      >
                        {{ dentist.first_name.charAt(0) }}{{ dentist.last_name.charAt(0) }}
                      </div>
                      <div>
                        <p 
                          class="text-base font-semibold"
                          :class="selectedDentist === dentist.user_id ? 'text-white' : 'text-gray-900'"
                        >
                          Dr. {{ dentist.first_name }} {{ dentist.last_name }}
                        </p>
                        <p 
                          class="text-sm"
                          :class="selectedDentist === dentist.user_id ? 'text-gray-200' : 'text-gray-500'"
                        >
                          Dentist
                        </p>
                      </div>
                    </div>
                  </button>
                </div>
              </div>
              
              <div class="mb-6">
                <label class="block text-md font-medium text-gray-700 mb-3">Select Treatments * <span class="text-sm text-gray-500">(Click to select/deselect)</span></label>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                  <button
                    v-for="treatment in treatments"
                    :key="treatment.treatment_id"
                    @click="toggleTreatment(treatment.treatment_id)"
                    class="rounded-lg p-4 text-left transition-all"
                    :class="selectedTreatments.includes(treatment.treatment_id) 
                      ? 'bg-white border-2 border-darkGreen-900' 
                      : 'bg-white border border-gray-300 hover:bg-gray-50'"
                  >
                    <div class="flex items-start gap-2">
                      <div class="flex-shrink-0 mt-0.5">
                        <div 
                          class="w-5 h-5 rounded border-2 flex items-center justify-center transition-colors"
                          :class="selectedTreatments.includes(treatment.treatment_id) 
                            ? 'bg-darkGreen-900 border-darkGreen-900' 
                            : 'border-gray-300'"
                        >
                          <svg v-if="selectedTreatments.includes(treatment.treatment_id)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                          </svg>
                        </div>
                      </div>
                      <div class="flex-1">
                        <p class="text-md font-semibold text-gray-900">{{ treatment.treatment_name }}</p>
                      </div>
                    </div>
                  </button>
                </div>
              </div>
              
              <div class="flex justify-between pt-4">
                <button
                  @click="goBackReschedule"
                  class="bg-white text-darkGreen-900 border-2 border-darkGreen-900 px-4 py-2 rounded-lg hover:bg-darkGreen-50 transition-colors text-md font-medium"
                >
                  Back
                </button>
                <button
                  @click="confirmDentistAndTreatments"
                  :disabled="!selectedDentist || selectedTreatments.length === 0"
                  class="bg-darkGreen-900 text-white px-4 py-2 rounded-lg hover:bg-darkGreen-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed text-md"
                >
                  Next
                </button>
              </div>
            </div>
          </div>

          <!-- Step 3: Select Schedule -->
          <div v-if="rescheduleStep === 'schedule'" class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-700">Step 3: Select Date and Time</h3>
            
            <!-- Selection Summary -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
              <p class="text-md font-medium text-green-900 mb-2">Your Selections:</p>
              <div class="text-md text-green-800 space-y-1">
                <p><strong>Branch:</strong> {{ branches.find(b => b.branch_id === selectedBranch)?.branch_name }}</p>
                <p><strong>Dentist:</strong> Dr. {{ dentists.find(d => d.user_id === selectedDentist)?.first_name }} {{ dentists.find(d => d.user_id === selectedDentist)?.last_name }}</p>
                <p><strong>Treatments:</strong> {{ selectedTreatments.map(id => treatments.find(t => t.treatment_id === id)?.treatment_name).join(', ') }}</p>
              </div>
            </div>

            <div v-if="isLoadingSchedules" class="text-center py-8">
              <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-darkGreen-900"></div>
              <p class="mt-2 text-gray-500">Loading schedules...</p>
            </div>
            <div v-else-if="schedules.length === 0" class="text-center py-8">
              <p class="text-gray-500">No available schedules for this dentist.</p>
            </div>
            <div v-else>
              <!-- Schedule Days in 2 Columns -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div v-for="[date, daySchedules] in paginatedSchedules" :key="String(date)" class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                  <h4 class="text-md font-medium text-gray-700 mb-3">{{ formatDateWithDay(String(date)) }}</h4>
                  <div class="grid grid-cols-3 gap-2">
                    <button
                      v-for="schedule in (daySchedules as Schedule[])"
                      :key="schedule.schedule_id"
                      @click="selectSchedule(schedule.schedule_id)"
                      class="px-3 py-2 rounded-md text-md font-medium transition-all"
                      :class="selectedSchedule === schedule.schedule_id 
                        ? 'bg-darkGreen-900 text-white' 
                        : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300'"
                    >
                      {{ formatTime(schedule.start_time) }}
                    </button>
                  </div>
                </div>
              </div>

              <!-- Pagination Controls -->
              <div v-if="totalPages > 1" class="flex items-center justify-center gap-2 pt-4">
                <button
                  @click="prevPage"
                  :disabled="currentPage === 1"
                  class="px-4 py-2 text-md font-medium rounded-lg border border-gray-300 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                >
                  ← Prev
                </button>
                
                <span class="px-4 py-2 text-md text-gray-600">
                  {{ currentPage }} / {{ totalPages }}
                </span>
                
                <button
                  @click="nextPage"
                  :disabled="currentPage === totalPages"
                  class="px-4 py-2 text-md font-medium rounded-lg border border-gray-300 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                >
                  Next →
                </button>
              </div>
            </div>
            <div class="flex justify-between pt-4">
              <button
                @click="goBackReschedule"
                class="bg-white text-darkGreen-900 border-2 border-darkGreen-900 px-4 py-2 rounded-lg hover:bg-darkGreen-50 transition-colors text-md font-medium"
              >
                Back
              </button>
              <button
                @click="confirmScheduleSelection"
                :disabled="!selectedSchedule"
                class="bg-darkGreen-900 text-white px-4 py-2 rounded-lg hover:bg-darkGreen-800 transition-colors text-md disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Next
              </button>
            </div>
          </div>

          <!-- Step 4: Confirm -->
          <div v-if="rescheduleStep === 'confirm'" class="space-y-6">
            <h3 class="text-lg font-semibold text-gray-700">Step 4: Confirm Reschedule</h3>
            
            <!-- Comparison View -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Current Appointment -->
              <div class="border-2 border-gray-300 rounded-lg p-4 bg-gray-50">
                <div class="flex items-center gap-2 mb-3">
                  <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  <h4 class="text-base font-bold text-gray-900">Current Appointment</h4>
                </div>
                <div class="space-y-2 text-md text-gray-700">
                  <p><strong>Branch:</strong> {{ appointment?.branch }}</p>
                  <p><strong>Dentist:</strong> {{ appointment?.dentist }}</p>
                  <p><strong>Date:</strong> {{ formatDateWithDay(appointment?.date || '') }}</p>
                  <p><strong>Time:</strong> {{ formatTime(appointment?.start_time || '') }} - {{ formatTime(appointment?.end_time || '') }}</p>
                </div>
              </div>

              <!-- New Appointment -->
              <div class="border-2 border-darkGreen-900 rounded-lg p-4 bg-darkGreen-50">
                <div class="flex items-center gap-2 mb-3">
                  <svg class="w-5 h-5 text-darkGreen-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  <h4 class="text-base font-bold text-darkGreen-900">Rescheduled Appointment Details</h4>
                </div>
                <div class="space-y-2 text-md text-gray-900">
                  <p><strong>Branch:</strong> {{ branches.find(b => b.branch_id === selectedBranch)?.branch_name }}</p>
                  <p><strong>Dentist:</strong> Dr. {{ dentists.find(d => d.user_id === selectedDentist)?.first_name }} {{ dentists.find(d => d.user_id === selectedDentist)?.last_name }}</p>
                  <p><strong>Date:</strong> {{ formatDateWithDay(schedules.find(s => s.schedule_id === selectedSchedule)?.schedule_date || '') }}</p>
                  <p><strong>Time:</strong> {{ formatTime(schedules.find(s => s.schedule_id === selectedSchedule)?.start_time || '') }} - {{ formatTime(schedules.find(s => s.schedule_id === selectedSchedule)?.end_time || '') }}</p>
                  <p><strong>Treatments:</strong> {{ selectedTreatments.map(id => treatments.find(t => t.treatment_id === id)?.treatment_name).join(', ') }}</p>
                </div>
              </div>
            </div>

            <div>
              <label class="block text-md font-medium text-gray-700 mb-2">Notes (Optional)</label>
              <textarea
                v-model="rescheduleNotes"
                rows="3"
                placeholder="Add any notes..."
                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900 resize-none"
              ></textarea>
            </div>
            <div>
              <label class="block text-md font-medium text-gray-700 mb-2">Reason for Reschedule <span class="text-red-600">*</span></label>
              <textarea
                v-model="rescheduleReason"
                rows="3"
                placeholder="Why are you rescheduling?"
                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900 resize-none"
                required
              ></textarea>
            </div>
            <div class="flex justify-between pt-4">
              <button
                @click="goBackReschedule"
                :disabled="isProcessing"
                class="bg-white text-darkGreen-900 border-2 border-darkGreen-900 px-4 py-2 rounded-lg hover:bg-darkGreen-50 transition-colors disabled:opacity-50 text-md font-medium"
              >
                Back
              </button>
              <button
                @click="openRescheduleConfirmModal"
                :disabled="isProcessing || !rescheduleReason.trim()"
                class="bg-darkGreen-900 text-white px-4 py-2 rounded-lg hover:bg-darkGreen-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed text-md"
              >
                Reschedule Appointment
              </button>
            </div>
          </div>
        </div>

        <!-- Reschedule Confirmation Modal -->
        <div v-if="showRescheduleConfirmModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
          <div class="bg-white rounded-xl shadow-2xl p-8 max-w-lg w-full mx-4">
            <h3 class="text-xl font-bold text-gray-900 mb-4">Confirm Reschedule?</h3>
            
            <div class="bg-red-50 border-2 border-red-200 rounded-lg p-4 mb-6 space-y-3">
              <div>
                <p class="text-md text-red-900 font-semibold mb-2">Are you sure?</p>
                <p class="text-md text-red-800">
                  This will cancel the current appointment and create a new one.
                </p>
              </div>

              <div class="border-t border-red-200 pt-3 space-y-2">
                <div class="flex items-center justify-between">
                </div>
                
                <p v-if="appointment.reschedule_count === 0" class="text-sm text-red-700 bg-red-100 px-3 py-2 rounded-md">
                  You will have <strong>2 more reschedule opportunities</strong> after this.
                </p>
                
                <p v-else-if="appointment.reschedule_count === 1" class="text-sm text-red-700 bg-red-100 px-3 py-2 rounded-md">
                  You will have <strong>1 more reschedule opportunity</strong> after this.
                </p>
                
                <p v-else-if="appointment.reschedule_count === 2" class="text-sm text-red-800 bg-red-200 px-3 py-2 rounded-md font-semibold">
                  This is your <strong>final reschedule</strong>. No more changes allowed after this.
                </p>
              </div>

              <div class="border-t border-red-200 pt-3">
                <p class="text-sm text-red-900 italic">
                  Note: You can only reschedule three times.
                </p>
              </div>
            </div>

            <div class="flex justify-end gap-3">
              <button
                @click="showRescheduleConfirmModal = false"
                :disabled="isProcessing"
                class="px-5 py-2.5 border-2 border-darkGreen-900 text-darkGreen-900 rounded-lg hover:bg-darkGreen-50 transition-all disabled:opacity-50 disabled:cursor-not-allowed font-semibold text-md"
              >
                Cancel
              </button>
              <button
                @click="submitReschedule"
                :disabled="isProcessing"
                class="px-5 py-2.5 bg-darkGreen-900 text-white rounded-lg hover:bg-darkGreen-800 transition-all disabled:opacity-50 disabled:cursor-not-allowed font-semibold text-md shadow-md hover:shadow-lg"
              >
                {{ isProcessing ? 'Processing...' : 'Yes, Reschedule' }}
              </button>
            </div>
          </div>
        </div>

        <!-- Cancel Modal -->
        <div v-if="showCancelModal" class="fixed inset-0 bg-black/20 backdrop-blur-sm bg-opacity-50 flex items-center justify-center z-50">
          <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-gray-900 mb-4">Cancel Appointment</h3>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
              <p class="text-md text-red-800">
                <strong>Warning:</strong> This action cannot be undone.
              </p>
            </div>
            <label class="block text-md font-medium text-gray-700 mb-2">Reason for Cancellation <span class="text-red-600">*</span></label>
            <textarea
              v-model="cancelReason"
              rows="4"
              placeholder="Please provide a reason..."
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900 resize-none"
            ></textarea>
            <div class="flex justify-end gap-3 mt-6">
              <button
                @click="closeCancelModal"
                :disabled="isProcessing"
                class="bg-white text-green-900 border-1 border-green-900 px-4 py-2 rounded-lg hover:bg-red-50 transition-colors text-md font-medium"
              >
                Cancel
              </button>
              <button
                @click="submitCancel"
                :disabled="!cancelReason.trim() || isProcessing"
                class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed text-md"
              >
                {{ isProcessing ? 'Processing...' : 'Confirm Cancellation' }}
              </button>
            </div>
          </div>
        </div>

        <!-- Status Update Modal -->
        <div v-if="showStatusModal" class="fixed inset-0 bg-black/20 backdrop-blur-sm bg-opacity-50 flex items-center justify-center z-50">
          <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-gray-900 mb-4">Update Appointment Status</h3>
            <div class="mb-4">
              <label class="block text-md font-medium text-gray-700 mb-2">Current Status</label>
              <span v-if="appointment" :class="`inline-block px-3 py-1 rounded-full text-md font-medium ${getStatusColor(appointment.status)}`">
                {{ appointment.status }}
              </span>
            </div>
            <div class="mb-4">
              <label class="block text-md font-medium text-gray-700 mb-2">New Status *</label>
              <select
                v-model="selectedStatus"
                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
              >
                <option v-for="status in statusOptions" :key="status" :value="status">
                  {{ status }}
                </option>
              </select>
            </div>
            <div v-if="selectedStatus === 'Cancelled' || selectedStatus === 'No Show'" class="mb-4">
              <label class="block text-md font-medium text-gray-700 mb-2">Reason for Status Change <span class="text-red-600">*</span></label>
              <textarea
                v-model="statusReason"
                rows="4"
                placeholder="Please provide a reason..."
                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900 resize-none"
              ></textarea>
            </div>
            <div class="flex justify-end gap-3 mt-6">
              <button
                @click="closeStatusModal"
                :disabled="isProcessing"
                class="bg-white text-blue-600 border-2 border-blue-600 px-4 py-2 rounded-lg hover:bg-blue-50 transition-colors text-md font-medium"
              >
                Cancel
              </button>
              <button
                @click="submitStatusUpdate"
                :disabled="(!selectedStatus || selectedStatus === appointment?.status || ((selectedStatus === 'Cancelled' || selectedStatus === 'No Show') && !statusReason.trim()) || isProcessing)"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed text-md"
              >
                {{ isProcessing ? 'Processing...' : 'Update Status' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.border-darkGreen-900 {
  border-color: #1e4f4f;
}

.bg-darkGreen-900 {
  background-color: #1e4f4f;
}

.text-darkGreen-900 {
  color: #1e4f4f;
}

.bg-darkGreen-800 {
  background-color: #2d5f5c;
}

.bg-darkGreen-50 {
  background-color: #e6f0f0;
}
</style>