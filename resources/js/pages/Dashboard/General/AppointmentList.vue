```vue
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

interface Appointment {
  appointment_id: string;
  patient_id: string;
  patient: string;
  date: string;
  time: string; // Expected as HH:mm:ss from backend
  branch: string;
  branch_id: string; // Added for branch-based filtering
  services: string[];
  dentist: string;
  dentist_id: string; // Added for dentist-based filtering
  status: string;
  balance: number;
}

interface User {
  user_id: string;
  first_name: string;
  last_name: string;
  user_type: string;
  branch_id?: string; // For branch-based filtering
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Appointments', href: '/appointments' },
];

const statusOptions = ['All Status', 'Scheduled', 'Completed', 'Cancelled'];
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

onMounted(async () => {
  try {
    // Prepare query parameters based on user_type
    const params: Record<string, string> = {};
    if (userType.value === 'Patient') {
      params.patient_id = userId.value;
    } else if (userType.value === 'Dentist') {
      params.dentist_id = userId.value;
    } else if (userType.value === 'Receptionist' && userBranchId.value) {
      params.branch_id = userBranchId.value;
    }
    // No params for Owner to fetch all appointments

    const response = await axios.get('/dashboard/appointments', {
      headers: { 'Cache-Control': 'no-cache' },
      params,
    });
    console.log('API Response:', response.data);

    appointments.value = response.data.appointments.map((appt: Appointment) => {
      console.log('Appointment Time:', appt.appointment_id, appt.time);
      return appt;
    });
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to load appointments. Please try again later.';
    console.error('Error fetching appointments:', err);
  } finally {
    isLoading.value = false;
  }
});

// Format date (e.g., August 22, 2025)
const formatDate = (date: string): string => {
  return date
    ? new Date(date).toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
      })
    : 'N/A';
};

// Format time for display (handle HH:mm:ss)
const formatTimeForDisplay = (time: string): string => {
  if (!time || time === 'N/A' || !time.match(/^\d{2}:\d{2}:\d{2}$/)) {
    console.log('Invalid time format:', time);
    return 'N/A';
  }
  return new Date(`1970-01-01T${time}`).toLocaleTimeString('en-US', {
    hour: 'numeric',
    minute: '2-digit',
  });
};

// Format time for <input type="time"> (e.g., HH:mm from HH:mm:ss)
const formatTimeForInput = (time: string): string => {
  if (!time || time === 'N/A') return '';
  return time.slice(0, 5);
};

// Format time for backend (e.g., HH:mm:ss from HH:mm)
const formatTimeForBackend = (time: string): string => {
  return time ? `${time}:00` : '00:00:00';
};

const serviceDropdownOpen = ref<Record<number, boolean>>({});
function toggleServiceDropdown(idx: number) {
  serviceDropdownOpen.value[idx] = !serviceDropdownOpen.value[idx];
}
function selectService(idx: number, service: string) {
  appointments.value[idx].services = [service];
  serviceDropdownOpen.value[idx] = false;
}

const filteredAppointments = computed(() => {
  let filtered = appointments.value;

  if (selectedStatus.value !== 'All Status') {
    filtered = filtered.filter(a => a.status === selectedStatus.value);
  }

  if (searchQuery.value) {
    filtered = filtered.filter(a =>
      a.patient.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
  }

  // Sort by date first, then time
  filtered = filtered.slice().sort((a, b) => {
    const dateA = new Date(a.date).getTime();
    const dateB = new Date(b.date).getTime();

    if (dateA !== dateB) {
      return dateA - dateB;
    }

    const [hA, mA, sA] = a.time.split(':').map(Number);
    const [hB, mB, sB] = b.time.split(':').map(Number);
    const timeA = hA * 3600 + mA * 60 + sA;
    const timeB = hB * 3600 + mB * 60 + sB;

    return timeA - timeB;
  });

  return filtered;
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

const showEditModal = ref(false);
const editAppointment = ref<Appointment | null>(null);
const editIndex = ref<number | null>(null);

function openEditModal(appt: Appointment, idx: number) {
  editAppointment.value = {
    ...JSON.parse(JSON.stringify(appt)),
    time: formatTimeForInput(appt.time),
  };
  editIndex.value = idx;
  showEditModal.value = true;
}
function closeEditModal() {
  showEditModal.value = false;
  editAppointment.value = null;
  editIndex.value = null;
}
function saveEdit() {
  if (editAppointment.value !== null && editIndex.value !== null) {
    const payload = {
      ...editAppointment.value,
      time: formatTimeForBackend(editAppointment.value.time),
    };
    axios
      .put(`/appointments/${editAppointment.value.appointment_id}`, payload)
      .then(() => {
        appointments.value[editIndex.value!] = JSON.parse(JSON.stringify(payload));
        closeEditModal();
      })
      .catch(err => {
        console.error('Error updating appointment:', err);
        error.value = 'Failed to save appointment. Please try again.';
      });
  }
}
</script>

<template>
  <Head title="Appointments" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">Appointments</h1>
      </div>
      <div v-if="isLoading" class="text-center py-4 text-gray-500">
        Loading appointments...
      </div>
      <div v-else-if="error" class="text-center py-4 text-red-500">
        {{ error }}
      </div>
      <div v-else class="bg-white rounded-lg shadow-md p-6">
        <div class="flex flex-wrap items-center gap-4 mb-4">
          <div>
            <label class="mr-2 text-gray-700">Filter by Status:</label>
            <select
              v-model="selectedStatus"
              class="border rounded px-2 py-1 focus:outline-none focus:ring-2 focus:ring-green-900"
            >
              <option v-for="status in statusOptions" :key="status" :value="status">{{ status }}</option>
            </select>
          </div>
          <div class="ml-auto">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search by name..."
              class="border rounded px-3 py-1 focus:outline-none focus:ring-2 focus:ring-green-900"
            />
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full bg-white rounded-lg shadow overflow-hidden">
            <thead>
              <tr class="bg-[#1e4f4f] text-white rounded-t-lg">
                <th class="px-4 py-2 text-left">Patient Name</th>
                <th class="px-4 py-2 text-left">Date</th>
                <th class="px-4 py-2 text-left">Start Time</th>
                <th class="px-4 py-2 text-left">Branch</th>
                <th class="px-4 py-2 text-left">Dentist</th>
                <th class="px-4 py-2 text-left">Status</th>
                <th class="px-4 py-2 text-left">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(appt, idx) in paginatedAppointments"
                :key="appt.appointment_id"
                class="border-b hover:bg-gray-50"
                :class="{ 'rounded-b-lg': idx === paginatedAppointments.length - 1 }"
              >
                <td class="px-4 py-2">{{ appt.patient || 'N/A' }}</td>
                <td class="px-4 py-2">{{ formatDate(appt.date) }}</td>
                <td class="px-4 py-2">{{ formatTimeForDisplay(appt.time) }}</td>
                <td class="px-4 py-2">{{ appt.branch || 'N/A' }}</td>
                <td class="px-4 py-2">{{ appt.dentist || 'N/A' }}</td>
                <td class="px-4 py-2">{{ appt.status || 'N/A' }}</td>
                <td class="px-4 py-2">
                  <button
                    class="bg-[#3E7F7B] text-white px-8 py-1 rounded-lg"
                    @click="openEditModal(appt, idx)"
                  >
                    Edit
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="filteredAppointments.length === 0" class="text-center py-4 text-gray-500">
          No appointments found.
        </div>
        <div class="flex items-center justify-between mt-4">
          <div>
            <label class="mr-2 text-gray-700">Rows per page:</label>
            <select
              v-model="rowsPerPage"
              class="border rounded px-2 py-1 focus:outline-none focus:ring-2 focus:ring-green-900"
            >
              <option :value="10">10</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
            </select>
          </div>
          <div class="flex items-center gap-2">
            <button
              @click="prevPage"
              :disabled="currentPage === 1"
              class="px-2 py-1 rounded border disabled:opacity-50 hover:bg-gray-100"
            >
              &lt;
            </button>
            <span class="text-gray-700">Page {{ currentPage }} of {{ totalPages }}</span>
            <button
              @click="nextPage"
              :disabled="currentPage === totalPages"
              class="px-2 py-1 rounded border disabled:opacity-50 hover:bg-gray-100"
            >
              &gt;
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Appointment Modal -->
    <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/20 backdrop-blur-sm">
      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg relative">
        <h2 class="text-xl font-bold mb-4">Edit Appointment</h2>
        <form v-if="editAppointment" @submit.prevent="saveEdit">
          <div class="mb-3">
            <label class="block text-sm font-medium mb-1">Patient Name</label>
            <input v-model="editAppointment.patient" class="border rounded px-3 py-1 w-full" required />
          </div>
          <div class="mb-3">
            <label class="block text-sm font-medium mb-1">Date</label>
            <input v-model="editAppointment.date" type="date" class="border rounded px-3 py-1 w-full" required />
          </div>
          <div class="mb-3">
            <label class="block text-sm font-medium mb-1">Start Time</label>
            <input
              v-model="editAppointment.time"
              type="time"
              class="border rounded px-3 py-1 w-full"
              required
            />
          </div>
          <div class="mb-3">
            <label class="block text-sm font-medium mb-1">Branch</label>
            <input v-model="editAppointment.branch" class="border rounded px-3 py-1 w-full" required />
          </div>
          <div class="mb-3">
            <label class="block text-sm font-medium mb-1">Dentist</label>
            <input v-model="editAppointment.dentist" class="border rounded px-3 py-1 w-full" required />
          </div>
          <div class="mb-3">
            <label class="block text-sm font-medium mb-1">Status</label>
            <select v-model="editAppointment.status" class="border rounded px-3 py-1 w-full">
              <option value="Scheduled">Scheduled</option>
              <option value="Completed">Completed</option>
              <option value="Cancelled">Cancelled</option>
            </select>
          </div>
          <div class="flex justify-end gap-2 mt-4">
            <button
              type="button"
              @click="closeEditModal"
              class="px-4 py-1 rounded border hover:bg-gray-100"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="bg-green-900 text-white px-4 py-1 rounded hover:bg-green-700"
            >
              Save
            </button>
          </div>
        </form>
        <button
          @click="closeEditModal"
          class="absolute top-2 right-2 text-gray-400 hover:text-gray-700 text-2xl"
        >
          &times;
        </button>
      </div>
    </div>
  </AppLayout>
</template>
```