<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Patient Records', href: '/patient-records' },
];

const searchQuery = ref('');
const selectedStatus = ref('All Patients');
const selectedBranch = ref('All Branches');
const currentPage = ref(1);
const itemsPerPage = ref(10);

// Modal states
const showViewModal = ref(false);
const showEditModal = ref(false);
const selectedPatient = ref<any>(null);

// Sample patient data - in real app, this would come from API
const patients = ref([
    {
        id: 'P001',
        name: 'John Smith',
        age: 35,
        gender: 'Male',
        phone: '+63 912 345 6789',
        email: 'john.smith@email.com',
        address: '123 Main St, Quezon City',
        branch: 'Main Branch',
        lastVisit: '2024-01-15',
        nextAppointment: '2024-02-15',
        status: 'Active',
        medicalHistory: 'No known allergies. Previous root canal treatment in 2023.',
        treatmentPlan: 'Regular cleaning every 6 months. Monitor cavity on tooth #14.',
        notes: 'Patient shows good oral hygiene practices. Recommend fluoride treatment.'
    },
    {
        id: 'P002',
        name: 'Maria Garcia',
        age: 28,
        gender: 'Female',
        phone: '+63 923 456 7890',
        email: 'maria.garcia@email.com',
        address: '456 Oak Ave, Makati City',
        branch: 'Main Branch',
        lastVisit: '2024-01-10',
        nextAppointment: '2024-01-25',
        status: 'Active',
        medicalHistory: 'Allergic to penicillin. No other medical conditions.',
        treatmentPlan: 'Complete cavity filling on tooth #19. Schedule crown placement.',
        notes: 'Patient experienced sensitivity after filling. Monitor for any complications.'
    },
    {
        id: 'P003',
        name: 'Robert Johnson',
        age: 45,
        gender: 'Male',
        phone: '+63 934 567 8901',
        email: 'robert.johnson@email.com',
        address: '789 Pine Rd, Taguig City',
        branch: 'Main Branch',
        lastVisit: '2023-12-20',
        nextAppointment: '2024-02-20',
        status: 'Active',
        medicalHistory: 'Hypertension controlled with medication. Diabetes type 2.',
        treatmentPlan: 'Regular monitoring due to medical conditions. Professional cleaning every 3 months.',
        notes: 'Patient needs special attention due to diabetes. Monitor gum health closely.'
    },
    {
        id: 'P004',
        name: 'Sarah Wilson',
        age: 32,
        gender: 'Female',
        phone: '+63 945 678 9012',
        email: 'sarah.wilson@email.com',
        address: '321 Elm St, Pasig City',
        branch: 'Main Branch',
        lastVisit: '2024-01-05',
        nextAppointment: '2024-03-05',
        status: 'Active',
        medicalHistory: 'No known medical conditions. Previous wisdom tooth extraction.',
        treatmentPlan: 'Regular checkup every 6 months. Monitor wisdom tooth area.',
        notes: 'Patient recovered well from extraction. No complications reported.'
    }
]);

const statusOptions = ['All Patients', 'Active', 'Inactive', 'New Patient'];
const branchOptions = ['All Branches', 'Main Branch', 'North Branch', 'South Branch'];

const filteredPatients = computed(() => {
    let filtered = patients.value;
    
    if (searchQuery.value) {
        filtered = filtered.filter(patient =>
            patient.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            patient.id.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            patient.email.toLowerCase().includes(searchQuery.value.toLowerCase())
        );
    }
    
    if (selectedStatus.value !== 'All Patients') {
        filtered = filtered.filter(patient => patient.status === selectedStatus.value);
    }
    
    if (selectedBranch.value !== 'All Branches') {
        filtered = filtered.filter(patient => patient.branch === selectedBranch.value);
    }
    
    return filtered;
});

const paginatedPatients = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredPatients.value.slice(start, end);
});

const totalPages = computed(() => {
    return Math.max(1, Math.ceil(filteredPatients.value.length / itemsPerPage.value));
});

const handleSearch = () => { currentPage.value = 1; };
const handleStatusFilter = (status: string) => { selectedStatus.value = status; currentPage.value = 1; };
const handleBranchFilter = (branch: string) => { selectedBranch.value = branch; currentPage.value = 1; };
const handlePageChange = (page: number) => { if (page >= 1 && page <= totalPages.value) currentPage.value = page; };

const handleViewPatient = (patientId: string) => {
    const patient = patients.value.find(p => p.id === patientId);
    selectedPatient.value = patient || null;
    showViewModal.value = true;
};

const handleEditPatient = (patientId: string) => {
    const patient = patients.value.find(p => p.id === patientId);
    selectedPatient.value = patient || null;
    showEditModal.value = true;
};

const closeViewModal = () => {
    showViewModal.value = false;
    selectedPatient.value = null;
};

const closeEditModal = () => {
    showEditModal.value = false;
    selectedPatient.value = null;
};

const savePatientChanges = () => {
    // In real app, this would save to API
    closeEditModal();
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'Active': return 'bg-green-100 text-green-800';
        case 'Inactive': return 'bg-gray-100 text-gray-800';
        case 'New Patient': return 'bg-blue-100 text-blue-800';
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
    <Head title="Patient Records" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">Patient Records</h1>
                <button class="bg-darkGreen-900 text-white px-4 py-2 rounded-lg hover:bg-darkGreen-800 transition-colors duration-200">
                    Add New Patient
                </button>
            </div>

            <!-- Search and Filter Section -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex flex-col sm:flex-row gap-4 mb-6">
                    <div class="flex-1">
                        <input
                            v-model="searchQuery"
                            @input="handleSearch"
                            type="text"
                            placeholder="Search by patient name, ID, or email..."
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                        />
                    </div>
                    <div class="flex gap-2">
                        <select
                            v-model="selectedStatus"
                            @change="handleStatusFilter"
                            class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                        >
                            <option v-for="status in statusOptions" :key="status" :value="status">
                                {{ status }}
                            </option>
                        </select>
                        <select
                            v-model="selectedBranch"
                            @change="handleBranchFilter"
                            class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                        >
                            <option v-for="branch in branchOptions" :key="branch" :value="branch">
                                {{ branch }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Patients Table -->
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse border border-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="border border-gray-200 py-3 px-4 text-left font-semibold text-gray-700">Patient ID</th>
                                <th class="border border-gray-200 py-3 px-4 text-left font-semibold text-gray-700">Patient Info</th>
                                <th class="border border-gray-200 py-3 px-4 text-left font-semibold text-gray-700">Contact</th>
                                <th class="border border-gray-200 py-3 px-4 text-left font-semibold text-gray-700">Branch</th>
                                <th class="border border-gray-200 py-3 px-4 text-left font-semibold text-gray-700">Last Visit</th>
                                <th class="border border-gray-200 py-3 px-4 text-left font-semibold text-gray-700">Next Appointment</th>
                                <th class="border border-gray-200 py-3 px-4 text-left font-semibold text-gray-700">Status</th>
                                <th class="border border-gray-200 py-3 px-4 text-left font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="paginatedPatients.length === 0">
                                <td colspan="8" class="text-center py-8 text-gray-500 text-lg">No patients found.</td>
                            </tr>
                            <tr v-for="patient in paginatedPatients" :key="patient.id" class="border-b last:border-b-0 hover:bg-gray-50">
                                <td class="py-3 px-4 font-medium">{{ patient.id }}</td>
                                <td class="py-3 px-4">
                                    <div>
                                        <div class="font-medium">{{ patient.name }}</div>
                                        <div class="text-sm text-gray-500">{{ patient.age }} years old, {{ patient.gender }}</div>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <div>
                                        <div class="text-sm">{{ patient.phone }}</div>
                                        <div class="text-sm text-gray-500">{{ patient.email }}</div>
                                    </div>
                                </td>
                                <td class="py-3 px-4">{{ patient.branch }}</td>
                                <td class="py-3 px-4">{{ formatDate(patient.lastVisit) }}</td>
                                <td class="py-3 px-4">{{ patient.nextAppointment ? formatDate(patient.nextAppointment) : 'Not scheduled' }}</td>
                                <td class="py-3 px-4">
                                    <span :class="`px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(patient.status)}`">
                                        {{ patient.status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex gap-2">
                                        <button
                                            @click="handleViewPatient(patient.id)"
                                            class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition text-sm"
                                        >View</button>
                                        <button
                                            @click="handleEditPatient(patient.id)"
                                            class="bg-darkGreen-900 text-white px-3 py-1 rounded hover:bg-darkGreen-800 transition text-sm"
                                        >Edit</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mt-4 gap-4">
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-700">Rows per page:</span>
                        <select
                            v-model="itemsPerPage"
                            @change="currentPage = 1"
                            class="border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                        >
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            @click="handlePageChange(currentPage - 1)"
                            :disabled="currentPage === 1"
                            class="border border-gray-300 rounded px-3 py-1 text-lg text-gray-700 bg-white disabled:opacity-50"
                        >&lt;</button>
                        <span class="text-sm text-gray-700">Page {{ totalPages === 0 ? 0 : currentPage }} of {{ totalPages === 0 ? 0 : totalPages }}</span>
                        <button
                            @click="handlePageChange(currentPage + 1)"
                            :disabled="currentPage === totalPages || totalPages === 0"
                            class="border border-gray-300 rounded px-3 py-1 text-lg text-gray-700 bg-white disabled:opacity-50"
                        >&gt;</button>
                    </div>
                </div>
            </div>

            <!-- View Patient Modal -->
            <div v-if="showViewModal" class="absolute inset-0 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-4xl mx-4 max-h-[90vh] overflow-y-auto">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-900">Patient Details</h2>
                        <button
                            @click="closeViewModal"
                            class="text-gray-400 hover:text-gray-600 text-2xl font-bold"
                        >&times;</button>
                    </div>
                    <div v-if="selectedPatient" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Patient ID</label>
                                <input type="text" :value="selectedPatient.id" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                <input type="text" :value="selectedPatient.name" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Age</label>
                                <input type="text" :value="selectedPatient.age" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                                <input type="text" :value="selectedPatient.gender" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                <input type="text" :value="selectedPatient.phone" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="text" :value="selectedPatient.email" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                                <input type="text" :value="selectedPatient.branch" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <input type="text" :value="selectedPatient.status" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                            <input type="text" :value="selectedPatient.address" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Medical History</label>
                            <textarea readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" rows="3">{{ selectedPatient.medicalHistory }}</textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Treatment Plan</label>
                            <textarea readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" rows="3">{{ selectedPatient.treatmentPlan }}</textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                            <textarea readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" rows="3">{{ selectedPatient.notes }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Patient Modal -->
            <div v-if="showEditModal" class="absolute inset-0 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-4xl mx-4 max-h-[90vh] overflow-y-auto">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-900">Edit Patient</h2>
                        <button
                            @click="closeEditModal"
                            class="text-gray-400 hover:text-gray-600 text-2xl font-bold"
                        >&times;</button>
                    </div>
                    <div v-if="selectedPatient" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Patient ID</label>
                                <input type="text" :value="selectedPatient.id" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                <input type="text" v-model="selectedPatient.name" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Age</label>
                                <input type="number" v-model="selectedPatient.age" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                                <select v-model="selectedPatient.gender" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900">
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                <input type="text" v-model="selectedPatient.phone" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" v-model="selectedPatient.email" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                                <select v-model="selectedPatient.branch" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900">
                                    <option value="Main Branch">Main Branch</option>
                                    <option value="North Branch">North Branch</option>
                                    <option value="South Branch">South Branch</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select v-model="selectedPatient.status" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="New Patient">New Patient</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                            <input type="text" v-model="selectedPatient.address" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Medical History</label>
                            <textarea v-model="selectedPatient.medicalHistory" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" rows="3"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Treatment Plan</label>
                            <textarea v-model="selectedPatient.treatmentPlan" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" rows="3"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                            <textarea v-model="selectedPatient.notes" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" rows="3"></textarea>
                        </div>

                        <div class="flex gap-2 pt-4">
                            <button
                                @click="savePatientChanges"
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
