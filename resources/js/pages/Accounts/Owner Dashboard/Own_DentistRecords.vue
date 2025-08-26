<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

interface Dentist {
    id: string;
    firstName: string;
    lastName: string;
    email: string;
    phoneNumber: string;
    position: string;
    branchName: string;
    branchId: string | null;
}

interface Branch {
    id: string;
    name: string;
}

interface DentistData {
    dentists: Dentist[];
    branches: Branch[];
    pagination: {
        currentPage: number;
        totalPages: number;
        perPage: number;
        totalRecords: number;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Dentist Records', href: '/records/dentists' },
];

const searchQuery = ref('');
const selectedBranch = ref('Select Branch');
const currentPage = ref(1);
const itemsPerPage = ref(10);

// Data state
const dentistData = ref<DentistData | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);

// Modal states
const showViewModal = ref(false);
const showEditModal = ref(false);
const selectedDentist = ref<Dentist | null>(null);

// Form data for edit modal
const editForm = ref({
    firstName: '',
    lastName: '',
    email: '',
    phoneNumber: '',
    position: '',
    branchName: ''
});

const filteredDentists = computed(() => {
    if (!dentistData.value) return [];
    
    let filtered = dentistData.value.dentists;
    if (searchQuery.value) {
        filtered = filtered.filter(dentist =>
            dentist.firstName.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            dentist.lastName.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            dentist.email.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            dentist.phoneNumber.includes(searchQuery.value)
        );
    }
    if (selectedBranch.value !== 'Select Branch') {
        filtered = filtered.filter(dentist => dentist.branchName === selectedBranch.value);
    }
    return filtered;
});

const paginatedDentists = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredDentists.value.slice(start, end);
});

const totalPages = computed(() => {
    return Math.max(1, Math.ceil(filteredDentists.value.length / itemsPerPage.value));
});

const fetchDentistData = async () => {
    try {
        loading.value = true;
        const response = await axios.get('/dashboard/owner/api/dentists');
        dentistData.value = response.data;
    } catch (err) {
        error.value = 'Failed to fetch dentist data';
        console.error('Error fetching dentist data:', err);
    } finally {
        loading.value = false;
    }
};

const handleSearch = () => { currentPage.value = 1; };
const handleBranchFilter = (branch: string) => { selectedBranch.value = branch; currentPage.value = 1; };
const handlePageChange = (page: number) => { if (page >= 1 && page <= totalPages.value) currentPage.value = page; };

const handleViewDentist = (dentistId: string) => {
    const dentist = dentistData.value?.dentists.find(d => d.id === dentistId);
    selectedDentist.value = dentist || null;
    showViewModal.value = true;
};

const handleEdit = () => {
    // For now, edit the first dentist as an example
    const firstDentist = dentistData.value?.dentists[0];
    if (firstDentist) {
        editForm.value = { 
            firstName: firstDentist.firstName,
            lastName: firstDentist.lastName,
            email: firstDentist.email,
            phoneNumber: firstDentist.phoneNumber,
            position: firstDentist.position,
            branchName: firstDentist.branchName
        };
    }
    showEditModal.value = true;
};

const handleEditDentist = (dentistId: string) => {
    const dentist = dentistData.value?.dentists.find(d => d.id === dentistId);
    if (dentist) {
        editForm.value = { 
            firstName: dentist.firstName,
            lastName: dentist.lastName,
            email: dentist.email,
            phoneNumber: dentist.phoneNumber,
            position: dentist.position,
            branchName: dentist.branchName
        };
    }
    showEditModal.value = true;
};

const closeViewModal = () => {
    showViewModal.value = false;
    selectedDentist.value = null;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editForm.value = {
        firstName: '',
        lastName: '',
        email: '',
        phoneNumber: '',
        position: '',
        branchName: ''
    };
};

const saveEdit = () => {
    // Handle save logic here
    console.log('Saving dentist:', editForm.value);
    closeEditModal();
};

onMounted(() => {
    fetchDentistData();
});
</script>

<template>
    <Head title="Dentist Records" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 rounded-xl p-4 bg-gray-50 min-h-screen relative">
            <!-- Loading State -->
            <div v-if="loading" class="flex justify-center items-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
                <p class="text-red-800">{{ error }}</p>
                <button 
                    @click="fetchDentistData" 
                    class="mt-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
                >
                    Try Again
                </button>
            </div>

            <!-- Main Content -->
            <div v-else-if="dentistData" class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-2 gap-4" :class="{ 'blur-[1px]': showViewModal || showEditModal }">
                    <h1 class="text-3xl font-bold text-gray-900">Dentist Records</h1>
                    <div class="flex flex-1 justify-end items-center gap-2">
                        <input
                            v-model="searchQuery"
                            @input="handleSearch"
                            type="text"
                            placeholder="Search by name..."
                            class="border border-gray-300 rounded px-4 py-2 w-64 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                        />
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-2 gap-4" :class="{ 'blur-[1px]': showViewModal || showEditModal }">
                    <div class="flex items-center gap-2">
                        <label class="font-medium text-gray-700 mr-2">Branch</label>
                        <select
                            v-model="selectedBranch"
                            @change="handleBranchFilter(selectedBranch)"
                            class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                        >
                            <option>Select Branch</option>
                            <option v-for="branch in dentistData.branches" :key="branch.id" :value="branch.name">{{ branch.name }}</option>
                        </select>
                    </div>
                    <button
                        @click="handleEdit"
                        class="bg-darkGreen-900 text-white px-6 py-2 rounded font-semibold shadow hover:bg-darkGreen-800 transition"
                    >Edit</button>
                </div>
                <div class="bg-white rounded-xl shadow p-0 overflow-x-auto" :class="{ 'blur-[1px]': showViewModal || showEditModal }">
                    <table class="w-full min-w-[900px] border-separate border-spacing-0">
                        <thead>
                            <tr class="bg-darkGreen-900 text-white">
                                <th class="py-3 px-4 text-left font-semibold">ID</th>
                                <th class="py-3 px-4 text-left font-semibold">Last Name</th>
                                <th class="py-3 px-4 text-left font-semibold">First Name</th>
                                <th class="py-3 px-4 text-left font-semibold">Email</th>
                                <th class="py-3 px-4 text-left font-semibold">Phone</th>
                                <th class="py-3 px-4 text-left font-semibold">Position</th>
                                <th class="py-3 px-4 text-left font-semibold">Branch</th>
                                <th class="py-3 px-4 text-left font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="paginatedDentists.length === 0">
                                <td colspan="8" class="text-center py-8 text-gray-500 text-lg">No dentist records found.</td>
                            </tr>
                            <tr v-for="dentist in paginatedDentists" :key="dentist.id" class="border-b last:border-b-0 hover:bg-gray-50">
                                <td class="py-3 px-4">{{ dentist.id }}</td>
                                <td class="py-3 px-4">{{ dentist.lastName }}</td>
                                <td class="py-3 px-4">{{ dentist.firstName }}</td>
                                <td class="py-3 px-4">{{ dentist.email }}</td>
                                <td class="py-3 px-4">{{ dentist.phoneNumber }}</td>
                                <td class="py-3 px-4">{{ dentist.position }}</td>
                                <td class="py-3 px-4">{{ dentist.branchName }}</td>
                                <td class="py-3 px-4">
                                    <button
                                        @click="handleViewDentist(dentist.id)"
                                        class="bg-darkGreen-900 text-white px-4 py-1 rounded hover:bg-darkGreen-800 transition"
                                    >View</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mt-4 gap-4" :class="{ 'blur-[1px]': showViewModal || showEditModal }">
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

            <!-- View Dentist Modal -->
            <div v-if="showViewModal" class="absolute inset-0 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">View Dentist Record</h2>
                    <div v-if="selectedDentist" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ID</label>
                            <input type="text" :value="selectedDentist.id" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                            <input type="text" :value="selectedDentist.firstName" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                            <input type="text" :value="selectedDentist.lastName" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" :value="selectedDentist.email" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                            <input type="text" :value="selectedDentist.phoneNumber" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Position</label>
                            <input type="text" :value="selectedDentist.position" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                            <input type="text" :value="selectedDentist.branchName" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button @click="closeViewModal" class="px-4 py-2 text-gray-700 bg-gray-200 rounded hover:bg-gray-300 transition">Close</button>
                    </div>
                </div>
            </div>

            <!-- Edit Dentist Modal -->
            <div v-if="showEditModal" class="absolute inset-0 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Edit Dentist Record</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                            <input v-model="editForm.firstName" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                            <input v-model="editForm.lastName" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input v-model="editForm.email" type="email" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                            <input v-model="editForm.phoneNumber" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Position</label>
                            <input v-model="editForm.position" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                            <select v-model="editForm.branchName" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900">
                                <option v-for="branch in dentistData?.branches || []" :key="branch.id" :value="branch.name">{{ branch.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button @click="closeEditModal" class="px-4 py-2 text-gray-700 bg-gray-200 rounded hover:bg-gray-300 transition">Cancel</button>
                        <button @click="saveEdit" class="px-4 py-2 text-white bg-darkGreen-900 rounded hover:bg-darkGreen-800 transition">Save Changes</button>
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
.bg-darkGreen-800 {
  background-color: #1a4545;
}
.bg-hoverGreen-700 {
  background-color: #2d6a6a;
}
.hover\:bg-darkGreen-800:hover {
  background-color: #1a4545;
}
.focus\:ring-darkGreen-900:focus {
  --tw-ring-color: #1e4f4f;
}
</style> 