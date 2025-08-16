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

const statusOptions = ['All Status', 'Scheduled', 'In Progress', 'Completed', 'Cancelled', 'No Show'];
const selectedStatus = ref('All Status');
const selectedDate = ref(new Date().toISOString().split('T')[0]);
const selectedBranch = ref('All Branches');
const searchQuery = ref('');
const rowsPerPage = ref(10);
const currentPage = ref(1);

interface Appointment {
  id: string;
  patient: string;
  patientId: string;
  date: string;
  time: string;
  branch: string;
  services: string[];
  dentist: string;
  status: string;
  notes?: string;
  phone?: string;
  email?: string;
}

const appointments = ref<Appointment[]>([]);

onMounted(async () => {
  // @ts-ignore
  const data = await import('@/tempData/dashboardData.json');
  appointments.value = (data.default?.scheduledAppointments || data.scheduledAppointments).map((a: any) => ({
    id: a.id || Math.random().toString(36).substr(2, 9),
    patient: a.patientName,
    patientId: a.patientId || 'P001',
    date: a.date,
    time: a.startTime,
    branch: a.branch,
    services: a.services || ['General Checkup'],
    dentist: a.dentist || 'Dr. John Doe',
    status: a.status || 'Scheduled',
    notes: a.notes || '',
    phone: a.phone || '+63 912 345 6789',
    email: a.email || 'patient@email.com'
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

const branchOptions = ['All Branches', 'Main Branch', 'North Branch', 'South Branch'];

const filteredAppointments = computed(() => {
  let filtered = appointments.value;
  
  if (selectedStatus.value !== 'All Status') {
    filtered = filtered.filter(a => a.status === selectedStatus.value);
  }
  
  if (selectedDate.value) {
    filtered = filtered.filter(a => a.date === selectedDate.value);
  }
  
  if (selectedBranch.value !== 'All Branches') {
    filtered = filtered.filter(a => a.branch === selectedBranch.value);
  }
  
  if (searchQuery.value) {
    filtered = filtered.filter(a => 
      a.patient.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      a.patientId.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      a.dentist.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
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
const showAddModal = ref(false);
const editAppointment = ref<Appointment | null>(null);
const editIndex = ref<number | null>(null);

// Form data for add appointment modal
const newAppointment = ref({
    patient: '',
    patientId: '',
    date: '',
    time: '',
    branch: '',
    services: ['General Checkup'],
    dentist: '',
    phone: '',
    email: '',
    notes: ''
});

function openEditModal(appt: Appointment, idx: number) {
  // Deep clone to avoid mutating table until save
  editAppointment.value = JSON.parse(JSON.stringify(appt));
  editIndex.value = idx;
  showEditModal.value = true;
}

function openAddModal() {
  newAppointment.value = {
    patient: '',
    patientId: '',
    date: '',
    time: '',
    branch: '',
    services: ['General Checkup'],
    dentist: '',
    phone: '',
    email: '',
    notes: ''
  };
  showAddModal.value = true;
}

function closeEditModal() {
  showEditModal.value = false;
  editAppointment.value = null;
  editIndex.value = null;
}

function closeAddModal() {
  showAddModal.value = false;
}

function saveEdit() {
  if (editAppointment.value !== null && editIndex.value !== null) {
    appointments.value[editIndex.value] = JSON.parse(JSON.stringify(editAppointment.value));
  }
  closeEditModal();
}

function saveNewAppointment() {
  // In real app, this would save to API
  const appointment: Appointment = {
    id: Math.random().toString(36).substr(2, 9),
    ...newAppointment.value,
    status: 'Scheduled'
  };
  appointments.value.unshift(appointment);
  closeAddModal();
}

function updateStatus(appointmentId: string, newStatus: string) {
  const appointment = appointments.value.find(a => a.id === appointmentId);
  if (appointment) {
    appointment.status = newStatus;
  }
}

function cancelAppointment(appointmentId: string) {
  const appointment = appointments.value.find(a => a.id === appointmentId);
  if (appointment) {
    appointment.status = 'Cancelled';
  }
}

const getStatusColor = (status: string) => {
  switch (status) {
    case 'Scheduled': return 'bg-blue-100 text-blue-800';
    case 'In Progress': return 'bg-yellow-100 text-yellow-800';
    case 'Completed': return 'bg-green-100 text-green-800';
    case 'Cancelled': return 'bg-red-100 text-red-800';
    case 'No Show': return 'bg-gray-100 text-gray-800';
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

const formatTime = (timeString: string) => {
  return timeString;
};

const availableServices = [
    'General Checkup',
    'Dental Cleaning',
    'Cavity Filling',
    'Root Canal',
    'Tooth Extraction',
    'Crown',
    'Whitening',
    'Braces Consultation',
    'Emergency Treatment'
];

const availableDentists = [
    'Dr. John Doe',
    'Dr. Jane Smith',
    'Dr. Michael Johnson',
    'Dr. Sarah Wilson',
    'Dr. Robert Brown'
];
</script>

<template>
    <Head title="Appointment Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">Appointment Management</h1>
                <button
                    @click="openAddModal"
                    class="bg-darkGreen-900 text-white px-4 py-2 rounded-lg hover:bg-darkGreen-800 transition-colors duration-200"
                >
                    Schedule New Appointment
                </button>
            </div>

            <!-- Search and Filter Section -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex flex-col sm:flex-row gap-4 mb-6">
                    <div class="flex-1">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search by patient name, ID, or dentist..."
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
                        <input
                            v-model="selectedDate"
                            type="date"
                            class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                        />
                        <select
                            v-model="selectedBranch"
                            class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                        >
                            <option v-for="branch in branchOptions" :key="branch" :value="branch">
                                {{ branch }}
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
                                <th class="border border-gray-200 py-3 px-4 text-left font-semibold text-gray-700">Patient Info</th>
                                <th class="border border-gray-200 py-3 px-4 text-left font-semibold text-gray-700">Contact</th>
                                <th class="border border-gray-200 py-3 px-4 text-left font-semibold text-gray-700">Services</th>
                                <th class="border border-gray-200 py-3 px-4 text-left font-semibold text-gray-700">Dentist</th>
                                <th class="border border-gray-200 py-3 px-4 text-left font-semibold text-gray-700">Branch</th>
                                <th class="border border-gray-200 py-3 px-4 text-left font-semibold text-gray-700">Status</th>
                                <th class="border border-gray-200 py-3 px-4 text-left font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="paginatedAppointments.length === 0">
                                <td colspan="8" class="text-center py-8 text-gray-500 text-lg">No appointments found.</td>
                            </tr>
                            <tr v-for="(appointment, idx) in paginatedAppointments" :key="appointment.id" class="border-b last:border-b-0 hover:bg-gray-50">
                                <td class="py-3 px-4">
                                    <div class="font-medium">{{ formatDate(appointment.date) }}</div>
                                    <div class="text-sm text-gray-500">{{ formatTime(appointment.time) }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <div>
                                        <div class="font-medium">{{ appointment.patient }}</div>
                                        <div class="text-sm text-gray-500">ID: {{ appointment.patientId }}</div>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <div>
                                        <div class="text-sm">{{ appointment.phone }}</div>
                                        <div class="text-sm text-gray-500">{{ appointment.email }}</div>
                                    </div>
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
                                                    v-for="service in availableServices"
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
                                    <select
                                        v-model="appointment.status"
                                        @change="updateStatus(appointment.id, appointment.status)"
                                        class="px-2 py-1 rounded-full text-xs font-medium border-0 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                                        :class="getStatusColor(appointment.status)"
                                    >
                                        <option value="Scheduled">Scheduled</option>
                                        <option value="In Progress">In Progress</option>
                                        <option value="Completed">Completed</option>
                                        <option value="Cancelled">Cancelled</option>
                                        <option value="No Show">No Show</option>
                                    </select>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex gap-2">
                                        <button
                                            @click="openEditModal(appointment, idx)"
                                            class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition text-sm"
                                        >Edit</button>
                                        <button
                                            v-if="appointment.status === 'Scheduled'"
                                            @click="cancelAppointment(appointment.id)"
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
                <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-2xl mx-4">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-900">Edit Appointment</h2>
                        <button
                            @click="closeEditModal"
                            class="text-gray-400 hover:text-gray-600 text-2xl font-bold"
                        >&times;</button>
                    </div>
                    <div v-if="editAppointment" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Patient Name</label>
                                <input
                                    v-model="editAppointment.patient"
                                    type="text"
                                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Patient ID</label>
                                <input
                                    v-model="editAppointment.patientId"
                                    type="text"
                                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                                />
                            </div>
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
                                <label class="block text-sm font-medium text-gray-700 mb-1">Dentist</label>
                                <select
                                    v-model="editAppointment.dentist"
                                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                                >
                                    <option v-for="dentist in availableDentists" :key="dentist" :value="dentist">
                                        {{ dentist }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                                <select
                                    v-model="editAppointment.branch"
                                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                                >
                                    <option v-for="branch in branchOptions.slice(1)" :key="branch" :value="branch">
                                        {{ branch }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                            <input
                                v-model="editAppointment.phone"
                                type="text"
                                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                            />
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input
                                v-model="editAppointment.email"
                                type="email"
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

            <!-- Add Appointment Modal -->
            <div v-if="showAddModal" class="absolute inset-0 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-2xl mx-4">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-900">Schedule New Appointment</h2>
                        <button
                            @click="closeAddModal"
                            class="text-gray-400 hover:text-gray-600 text-2xl font-bold"
                        >&times;</button>
                    </div>
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Patient Name *</label>
                                <input
                                    v-model="newAppointment.patient"
                                    type="text"
                                    required
                                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Patient ID</label>
                                <input
                                    v-model="newAppointment.patientId"
                                    type="text"
                                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
                                <input
                                    v-model="newAppointment.date"
                                    type="date"
                                    required
                                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Time *</label>
                                <input
                                    v-model="newAppointment.time"
                                    type="time"
                                    required
                                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Dentist *</label>
                                <select
                                    v-model="newAppointment.dentist"
                                    required
                                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                                >
                                    <option value="">Select Dentist</option>
                                    <option v-for="dentist in availableDentists" :key="dentist" :value="dentist">
                                        {{ dentist }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Branch *</label>
                                <select
                                    v-model="newAppointment.branch"
                                    required
                                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                                >
                                    <option value="">Select Branch</option>
                                    <option v-for="branch in branchOptions.slice(1)" :key="branch" :value="branch">
                                        {{ branch }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                            <input
                                v-model="newAppointment.phone"
                                type="text"
                                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                            />
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input
                                v-model="newAppointment.email"
                                type="email"
                                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                            />
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                            <textarea
                                v-model="newAppointment.notes"
                                rows="3"
                                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                                placeholder="Any additional notes..."
                            ></textarea>
                        </div>
                        
                        <div class="flex gap-2 pt-4">
                            <button
                                @click="saveNewAppointment"
                                class="flex-1 bg-darkGreen-900 text-white px-4 py-2 rounded hover:bg-darkGreen-800 transition-colors duration-200"
                            >Schedule Appointment</button>
                            <button
                                @click="closeAddModal"
                                class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 transition-colors duration-200"
                            >Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template> 