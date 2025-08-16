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
  id: string;
  patient: string;
  date: string;
  time: string;
  branch: string;
  services: string[];
  dentist: string;
  status: string;
  notes?: string;
}

const appointments = ref<Appointment[]>([]);

onMounted(async () => {
  // @ts-ignore
  const data = await import('@/tempData/dashboardData.json');
  appointments.value = (data.default?.scheduledAppointments || data.scheduledAppointments).map((a: any) => ({
    id: a.id || Math.random().toString(36).substr(2, 9),
    patient: a.patientName,
    date: a.date,
    time: a.startTime,
    branch: a.branch,
    services: a.services || ['General Checkup'],
    dentist: a.dentist || 'Dr. John Doe',
    status: a.status || 'Scheduled',
    notes: a.notes || ''
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

const getStatusColor = (status: string) => {
  switch (status) {
    case 'Scheduled': return 'bg-blue-100 text-blue-800';
    case 'Completed': return 'bg-green-100 text-green-800';
    case 'Cancelled': return 'bg-red-100 text-red-800';
    default: return 'bg-gray-100 text-gray-800';
  }
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};
</script>

<template>
    <Head title="My Appointments" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">My Appointments</h1>
                <button class="bg-darkGreen-900 text-white px-4 py-2 rounded-lg hover:bg-darkGreen-800 transition-colors duration-200">
                    Book New Appointment
                </button>
            </div>

            <!-- Search and Filter Section -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex flex-col sm:flex-row gap-4 mb-6">
                    <div class="flex-1">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search appointments..."
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

                <!-- Appointments Table -->
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse border border-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="border border-gray-200 py-3 px-4 text-left font-semibold text-gray-700">Date & Time</th>
                                <th class="border border-gray-200 py-3 px-4 text-left font-semibold text-gray-700">Services</th>
                                <th class="border border-gray-200 py-3 px-4 text-left font-semibold text-gray-700">Dentist</th>
                                <th class="border border-gray-200 py-3 px-4 text-left font-semibold text-gray-700">Branch</th>
                                <th class="border border-gray-200 py-3 px-4 text-left font-semibold text-gray-700">Status</th>
                                <th class="border border-gray-200 py-3 px-4 text-left font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="paginatedAppointments.length === 0">
                                <td colspan="6" class="text-center py-8 text-gray-500 text-lg">No appointments found.</td>
                            </tr>
                            <tr v-for="(appointment, idx) in paginatedAppointments" :key="appointment.id" class="border-b last:border-b-0 hover:bg-gray-50">
                                <td class="py-3 px-4">
                                    <div class="font-medium">{{ formatDate(appointment.date) }}</div>
                                    <div class="text-sm text-gray-500">{{ appointment.time }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="relative">
                                        <button
                                            @click="toggleServiceDropdown(idx)"
                                            class="text-left w-full bg-gray-100 hover:bg-gray-200 px-3 py-1 rounded text-sm transition-colors duration-200"
                                        >
                                            {{ appointment.services.join(', ') }}
                                        </button>
                                        <div
                                            v-if="serviceDropdownOpen[idx]"
                                            class="absolute top-full left-0 mt-1 w-48 bg-white border border-gray-200 rounded-lg shadow-lg z-10"
                                        >
                                            <div class="p-2">
                                                <div
                                                    v-for="service in ['General Checkup', 'Dental Cleaning', 'Cavity Filling', 'Root Canal', 'Tooth Extraction']"
                                                    :key="service"
                                                    @click="selectService(idx, service)"
                                                    class="px-3 py-2 hover:bg-gray-100 rounded cursor-pointer text-sm"
                                                >
                                                    {{ service }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4">{{ appointment.dentist }}</td>
                                <td class="py-3 px-4">{{ appointment.branch }}</td>
                                <td class="py-3 px-4">
                                    <span :class="`px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(appointment.status)}`">
                                        {{ appointment.status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex gap-2">
                                        <button
                                            @click="openEditModal(appointment, idx)"
                                            class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition text-sm"
                                        >Edit</button>
                                        <button
                                            v-if="appointment.status === 'Scheduled'"
                                            class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 transition text-sm"
                                        >Cancel</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mt-4 gap-4">
                    <div class="text-sm text-gray-700">
                        Showing {{ (currentPage - 1) * rowsPerPage + 1 }} to {{ Math.min(currentPage * rowsPerPage, filteredAppointments.length) }} of {{ filteredAppointments.length }} results
                    </div>
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

            <!-- Edit Appointment Modal -->
            <div v-if="showEditModal" class="absolute inset-0 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-900">Edit Appointment</h2>
                        <button
                            @click="closeEditModal"
                            class="text-gray-400 hover:text-gray-600 text-2xl font-bold"
                        >&times;</button>
                    </div>
                    <div v-if="editAppointment" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                            <input
                                v-model="editAppointment.date"
                                type="date"
                                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Time</label>
                            <input
                                v-model="editAppointment.time"
                                type="time"
                                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                            <textarea
                                v-model="editAppointment.notes"
                                rows="3"
                                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                            ></textarea>
                        </div>
                        <div class="flex gap-2 pt-4">
                            <button
                                @click="saveEdit"
                                class="flex-1 bg-darkGreen-900 text-white px-4 py-2 rounded hover:bg-darkGreen-800 transition-colors duration-200"
                            >Save Changes</button>
                            <button
                                @click="closeEditModal"
                                class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 transition-colors duration-200"
                            >Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template> 