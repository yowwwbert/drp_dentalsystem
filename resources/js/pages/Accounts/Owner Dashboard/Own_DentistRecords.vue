<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

interface Dentist {
    dentist_id: string;
    first_name: string;
    last_name: string;
    email_address: string;
    phone_number: string;
    dentist_type: string;
    branch_name: string;
    branchId: string | null;
    status: string;
}

interface Branch {
    id: string;
    branch_name: string;
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
const showEditModal = ref(false);
const selectedDentist = ref<Dentist | null>(null);

// Form data for edit modal
const editForm = ref({
    first_name: '',
    last_name: '',
    email_address: '',
    phone_number: '',
    dentist_type: '',
    branch_name: '',
    status: '',
});

const positions = ['Dentist', 'Head Dentist', 'Dental Assistant'];

// Use Inertia's route helper
const { props } = usePage() as { props: Record<string, any> };
const route = (name: string, params: Record<string, any> = {}) => {
    // Use window.route if it exists and is a function, otherwise fallback to props.route
    const routeHelper =
        typeof (window as any).route === 'function'
            ? (window as any).route
            : props.route;
    let url = routeHelper(name, params);
    return url;
};

const filteredDentists = computed(() => {
    if (!dentistData.value) return [];

    let filtered = dentistData.value.dentists;
    if (searchQuery.value) {
        filtered = filtered.filter(dentist =>
            dentist.first_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            dentist.last_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            dentist.email_address.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            dentist.phone_number.includes(searchQuery.value)
        );
    }
    if (selectedBranch.value !== 'Select Branch') {
        filtered = filtered.filter(dentist => dentist.branch_name === selectedBranch.value);
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
        const response = await axios.get(route('owner.dentists.data'));
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

const handleEditDentist = (dentist_id: string) => {
    const dentist = dentistData.value?.dentists.find(d => d.dentist_id === dentist_id);
    if (dentist) {
        editForm.value = {
            first_name: dentist.first_name,
            last_name: dentist.last_name,
            email_address: dentist.email_address,
            phone_number: dentist.phone_number,
            dentist_type: dentist.dentist_type,
            branch_name: dentist.branch_name,
            status: dentist.status
        };
        selectedDentist.value = dentist;
    }
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editForm.value = { first_name: '', last_name: '', email_address: '', phone_number: '', dentist_type: '', branch_name: '', status: '' };
    selectedDentist.value = null;
};

const saveEdit = async () => {
    try {
        if (!selectedDentist.value) return;

        const response = await axios.put(route('owner.dentists.update', { id: selectedDentist.value.dentist_id }), editForm.value);
        if (response.data.success) {
            const index = dentistData.value?.dentists.findIndex(d => d.dentist_id === selectedDentist.value?.dentist_id);
            if (typeof index === 'number' && index !== -1 && dentistData.value) {
                dentistData.value.dentists[index] = { ...dentistData.value.dentists[index], ...editForm.value };
            }
            closeEditModal();
        }
    } catch (err) {
        error.value = 'Failed to save dentist data';
        console.error('Error saving dentist data:', err);
    }
};

const viewDentist = (dentist_id: string) => {
    router.visit(route('owner.dentist.records', { dentist_id }));
};

onMounted(() => {
    fetchDentistData();
});
</script>

<template>
    <Head title="Dentist Records" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 rounded-xl p-4 bg-gray-50 min-h-screen relative">
            <div v-if="loading" class="flex justify-center items-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>
            <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
                <p class="text-red-800">{{ error }}</p>
                <button @click="fetchDentistData" class="mt-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Try Again</button>
            </div>
            <div v-else-if="dentistData" class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-2 gap-4" :class="{ 'blur-[1px]': showEditModal }">
                    <h1 class="text-3xl font-bold text-gray-900">Dentist Records</h1>
                    <div class="flex flex-1 justify-end items-center gap-2">
                        <input v-model="searchQuery" @input="handleSearch" type="text" placeholder="Search by name..." class="border border-gray-300 rounded px-4 py-2 w-64 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-2 gap-4" :class="{ 'blur-[1px]': showEditModal }">
                    <div class="flex items-center gap-2">
                        <label class="font-medium text-gray-700 mr-2">Branch</label>
                        <select v-model="selectedBranch" @change="handleBranchFilter(selectedBranch)" class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900">
                            <option>Select Branch</option>
                            <option v-for="branch in dentistData.branches" :key="branch.id" :value="branch.branch_name">{{ branch.branch_name }}</option>
                        </select>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow p-0 overflow-x-auto" :class="{ 'blur-[1px]': showEditModal }">
                    <table class="w-full min-w-[900px] border-separate border-spacing-0">
                        <thead>
                            <tr class="bg-darkGreen-900 text-white">
                                <th class="py-3 px-4 text-left font-semibold">Last Name</th>
                                <th class="py-3 px-4 text-left font-semibold">First Name</th>
                                <th class="py-3 px-4 text-left font-semibold">email_address</th>
                                <th class="py-3 px-4 text-left font-semibold">Phone</th>
                                <th class="py-3 px-4 text-left font-semibold">Position</th>
                                <th class="py-3 px-4 text-left font-semibold">Branch</th>
                                <th class="py-3 px-4 text-left font-semibold">Status</th>
                                <th class="py-3 px-4 text-left font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="paginatedDentists.length === 0">
                                <td colspan="9" class="text-center py-8 text-gray-500 text-lg">No dentist records found.</td>
                            </tr>
                            <tr v-for="dentist in paginatedDentists" :key="dentist.dentist_id" class="border-b last:border-b-0 hover:bg-gray-50">
                                <td class="py-3 px-4">{{ dentist.last_name || 'N/A' }}</td>
                                <td class="py-3 px-4">{{ dentist.first_name || 'N/A' }}</td>
                                <td class="py-3 px-4">{{ dentist.email_address || 'N/A' }}</td>
                                <td class="py-3 px-4">{{ dentist.phone_number || 'N/A' }}</td>
                                <td class="py-3 px-4">{{ dentist.dentist_type || 'N/A' }}</td>
                                <td class="py-3 px-4">{{ dentist.branch_name || 'N/A' }}</td>
                                <td class="py-3 px-4"><span :class="{
                                    'px-2 py-1 rounded-full text-md font-medium': true,
                                    'bg-green-100 text-green-800': dentist.status === 'Active',
                                    'bg-red-100 text-red-800': dentist.status === 'Inactive'
                                }">{{ dentist.status || 'N/A' }}</span></td>
                                <td class="py-3 px-4">
                                    <button @click="viewDentist(dentist.dentist_id)" class="bg-blue-600 text-white px-4 py-1 rounded hover:bg-blue-700 transition">View</button>
                                    <button @click="handleEditDentist(dentist.dentist_id)" class="bg-darkGreen-900 text-white px-4 py-1 rounded hover:bg-darkGreen-800 transition">Edit</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mt-4 gap-4" :class="{ 'blur-[1px]': showEditModal }">
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-700">Rows per page:</span>
                        <select v-model="itemsPerPage" @change="currentPage = 1" class="border border-gray-300 rounded px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-darkGreen-900">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="handlePageChange(currentPage - 1)" :disabled="currentPage === 1" class="border border-gray-300 rounded px-3 py-1 text-lg text-gray-700 bg-white disabled:opacity-50">&lt;</button>
                        <span class="text-sm text-gray-700">Page {{ totalPages === 0 ? 0 : currentPage }} of {{ totalPages === 0 ? 0 : totalPages }}</span>
                        <button @click="handlePageChange(currentPage + 1)" :disabled="currentPage === totalPages || totalPages === 0" class="border border-gray-300 rounded px-3 py-1 text-lg text-gray-700 bg-white disabled:opacity-50">&gt;</button>
                    </div>
                </div>
            </div>
            <div v-if="showEditModal" class="absolute inset-0 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Edit Dentist Record</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">First Name</label>
                            <input v-model="editForm.first_name" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900 text-gray-500 bg-gray-300" disabled/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Last Name</label>
                            <input v-model="editForm.last_name" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900 text-gray-500 bg-gray-300" disabled/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                            <input v-model="editForm.email_address" type="email" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                            <input v-model="editForm.phone_number" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Position</label>
                            <select v-model="editForm.dentist_type" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900">
                                <option v-for="position in positions" :key="position" :value="position">{{ position }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                            <select v-model="editForm.branch_name" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900">
                                <option v-for="branch in dentistData?.branches || []" :key="branch.id" :value="branch.branch_name">{{ branch.branch_name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select v-model="editForm.status" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
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
.bg-darkGreen-900 { background-color: #1e4f4f; }
.bg-darkGreen-800 { background-color: #1a4545; }
.bg-hoverGreen-700 { background-color: #2d6a6a; }
.hover\:bg-darkGreen-800:hover { background-color: #1a4545; }
.focus\:ring-darkGreen-900:focus { --tw-ring-color: #1e4f4f; }
.bg-blue-600 { background-color: #2563eb; }
.hover\:bg-blue-700:hover { background-color: #1d4ed8; }
</style>