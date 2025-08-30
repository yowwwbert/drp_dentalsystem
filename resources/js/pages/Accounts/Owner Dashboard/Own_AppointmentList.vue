<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Appointments',
        href: '/appointments',
    },
];

const statusOptions = ['All Status', 'Scheduled', 'Completed', 'Cancelled'];
const selectedStatus = ref('All Status');
const searchQuery = ref('');
const rowsPerPage = ref(10);
const currentPage = ref(1);

interface Appointment {
  patient: string;
  date: string;
  time: string;
  branch: string;
  services: string[];
  dentist: string;
  status: string;
}

const appointments = ref<Appointment[]>([]);

onMounted(async () => {
  // @ts-ignore
  const data = await import('@/tempData/dashboardData.json');
  appointments.value = (data.default?.scheduledAppointments || data.scheduledAppointments).map((a: any) => ({
    patient: a.patientName,
    date: a.date,
    time: a.startTime,
    branch: a.branch,
    services: a.services || ['General Checkup'],
    dentist: a.dentist || 'Dr. John Doe',
    status: a.status || 'Scheduled',
  }));
});

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
    filtered = filtered.filter(a => a.patient.toLowerCase().includes(searchQuery.value.toLowerCase()));
  }
  return filtered;
});

const paginatedAppointments = computed(() => {
  const start = (currentPage.value - 1) * rowsPerPage.value;
  return filteredAppointments.value.slice(start, start + rowsPerPage.value);
});

const totalPages = computed(() => Math.ceil(filteredAppointments.value.length / rowsPerPage.value));
function prevPage() { if (currentPage.value > 1) currentPage.value--; }
function nextPage() { if (currentPage.value < totalPages.value) currentPage.value++; }

const showEditModal = ref(false);
const editAppointment = ref<Appointment | null>(null);
const editIndex = ref<number | null>(null);

function openEditModal(appt: Appointment, idx: number) {
  // Deep clone to avoid mutating table until save
  editAppointment.value = JSON.parse(JSON.stringify(appt));
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
    appointments.value[editIndex.value] = JSON.parse(JSON.stringify(editAppointment.value));
  }
  closeEditModal();
}
</script>

<template>
  <Head title="Appointments" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-4 rounded-xl p-4 bg-gray-50 min-h-screen relative">
        <!-- Appointment Records Table Section -->
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-900">Appointment List</h1>
        </div>
        <div class="flex flex-wrap items-center gap-4 mb-4 mt-4">
          <div>
            <label class="font-medium mr-2 text-gray-700">Filter by Status:</label>
            <select v-model="selectedStatus" class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-900">
              <option v-for="status in statusOptions" :key="status" :value="status">{{ status }}</option>
            </select>
          </div>
          <div class="ml-auto">
            <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="Search by name..." class="border border-gray-300 rounded px-4 py-2 w-64 focus:outline-none focus:ring-2 focus:ring-teal-900" />
          </div>
        </div>
        <div class="bg-white rounded-xl shadow p-0 overflow-x-auto">
          <table class="w-full min-w-[900px] border-separate border-spacing-0">
            <thead>
              <tr class="bg-teal-900 text-white">
                <th class="px-3 py-4 text-left font-semibold">Patient Name</th>
                <th class="px-3 py-4 text-left font-semibold">Date</th>
                <th class="px-3 py-4 text-left font-semibold">Start Time</th>
                <th class="px-3 py-4 text-left font-semibold">Branch</th>
                <th class="px-3 py-4 text-left font-semibold">Services</th>
                <th class="px-3 py-4 text-left font-semibold">Dentist in Charge</th>
                <th class="px-3 py-4 text-left font-semibold">Status</th>
                <th class="px-3 py-4 text-left font-semibold">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(appt, idx) in paginatedAppointments" :key="idx" class="border-b last:border-b-0 hover:bg-gray-50" :class="{'rounded-b-lg': idx === paginatedAppointments.length - 1}">
                <td class="px-4 py-3">{{ appt.patient }}</td>
                <td class="px-4 py-3">{{ appt.date }}</td>
                <td class="px-4 py-3">{{ appt.time }}</td>
                <td class="px-4 py-3">{{ appt.branch }}</td>
                <td class="px-4 py-3">
                  <span v-if="appt.services.length === 1">{{ appt.services[0] }}</span>
                  <span v-else class="relative inline-block">
                    <button @click="toggleServiceDropdown(idx)" class="text-teal-900 hover:underline flex items-center">
                      {{ appt.services[0] }}
                      <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                      </svg>
                    </button>
                    <div v-if="serviceDropdownOpen[idx]" class="absolute left-0 mt-1 w-40 bg-white border border-gray-200 rounded shadow z-10">
                      <div v-for="service in appt.services" :key="service" @click="selectService(idx, service)" class="px-4 py-2 hover:bg-teal-50 cursor-pointer text-gray-700">
                        {{ service }}
                      </div>
                    </div>
                  </span>
                </td>
                <td class="px-4 py-3">{{ appt.dentist }}</td>
                <td class="px-4 py-3">{{ appt.status }}</td>
                <td class="px-4 py-3">
                  <button class="bg-teal-900 text-white px-4 py-1 rounded hover:bg-teal-700 transition" @click="openEditModal(appt, idx)">Edit</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mt-4 gap-4">
          <div>
            <label class="text-sm text-gray-700 mr-2">Rows per page:</label>
            <select v-model="rowsPerPage" class="border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-teal-900">
              <option :value="10">10</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
            </select>
          </div>
          <div class="flex items-center gap-2">
            <button @click="prevPage" :disabled="currentPage === 1" class="border border-gray-300 rounded px-3 py-1 text-lg text-gray-700 bg-white disabled:opacity-50">&lt;</button>
            <span>Page {{ currentPage }} of {{ totalPages }}</span>
            <button @click="nextPage" :disabled="currentPage === totalPages" class="border border-gray-300 rounded px-3 py-1 text-lg text-gray-700 bg-white disabled:opacity-50">&gt;</button>
          </div>
        </div>
    </div>
  </AppLayout>

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
          <input v-model="editAppointment.date" class="border rounded px-3 py-1 w-full" required />
        </div>
        <div class="mb-3">
          <label class="block text-sm font-medium mb-1">Start Time</label>
          <input v-model="editAppointment.time" class="border rounded px-3 py-1 w-full" required />
        </div>
        <div class="mb-3">
          <label class="block text-sm font-medium mb-1">Branch</label>
          <input v-model="editAppointment.branch" class="border rounded px-3 py-1 w-full" required />
        </div>
        <div class="mb-3">
          <label class="block text-sm font-medium mb-1">Services</label>
          <input v-model="editAppointment.services[0]" class="border rounded px-3 py-1 w-full" required />
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
          <button type="button" @click="closeEditModal" class="px-4 py-1 rounded border">Cancel</button>
          <button type="submit" class="bg-teal-900 text-white px-4 py-1 rounded hover:bg-teal-700">Save</button>
        </div>
      </form>
      <button @click="closeEditModal" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700 text-2xl">&times;</button>
    </div>
  </div>
</template> 