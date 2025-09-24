<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';

interface Branch {
    branch_id: string;
    branch_name: string;
    branch_address: string;
    branch_contact: string;
    branch_email: string;
    branch_logo?: string;
    branch_image?: string;
    branch_map?: string;
    branch_facebook?: string;
    branch_instagram?: string;
    operating_days?: string[] | null;
    opening_time?: string;
    closing_time?: string;
}

interface BranchData {
    branches: Branch[];
    pagination: {
        currentPage: number;
        totalPages: number;
        perPage: number;
        totalRecords: number;
    };
}

const page = usePage();
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Branch Settings', href: '/clinic/branches' },
];

const searchQuery = ref('');
const currentPage = ref(1);
const itemsPerPage = ref(10);

// Data state
const branchData = ref<BranchData | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);

// Modal states
const showEditModal = ref(false);
const showAddModal = ref(false);
const selectedBranch = ref<Branch | null>(null);

// Form data for add/edit modal
const branchForm = ref({
    branch_name: '',
    branch_address: '',
    contactNumber: '',
    email: '',
    logo: '',
    image: '',
    map: '',
    facebook: '',
    instagram: '',
    operatingDays: [] as string[],
    openingTime: '',
    closingTime: '',
});

// Form validation state
const formErrors = ref<{ [key: string]: string }>({});

// Available days for checkbox selection
const availableDays = [
    'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'
];

const filteredBranches = computed(() => {
    if (!branchData.value) return [];
    let filtered = branchData.value.branches;
    if (searchQuery.value) {
        filtered = filtered.filter(branch =>
            (branch.branch_name?.toLowerCase().includes(searchQuery.value.toLowerCase()) || false) ||
            (branch.branch_address?.toLowerCase().includes(searchQuery.value.toLowerCase()) || false) ||
            (branch.branch_email?.toLowerCase().includes(searchQuery.value.toLowerCase()) || false)
        );
    }
    return filtered;
});

const paginatedBranches = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredBranches.value.slice(start, end);
});

const totalPages = computed(() => {
    return Math.max(1, Math.ceil(filteredBranches.value.length / itemsPerPage.value));
});

// Helper function to parse operating_days
const parseOperatingDays = (days: string | string[] | null): string[] => {
    if (!days) return [];
    if (Array.isArray(days)) return days;
    try {
        return JSON.parse(days) as string[];
    } catch (e) {
        console.error('Failed to parse operating_days:', e);
        return [];
    }
};

// Format time to H:i (e.g., "09:00:00" or "09:00" to "09:00")
const formatTime = (time: string): string => {
    const [hours, minutes] = time.split(':');
    let formattedHours = parseInt(hours, 10);
    return `${formattedHours.toString().padStart(2, '0')}:${minutes.slice(0, 2)}`;
};

const fetchBranchData = async () => {
    try {
        loading.value = true;
        const response = await axios.get('/dashboard/owner/api/branches');
        const branches = response.data.branches.map((branch: any) => ({
            ...branch,
            operating_days: parseOperatingDays(branch.operating_days)
        }));
        branchData.value = {
            branches,
            pagination: response.data.pagination
        };
    } catch (err) {
        error.value = 'Failed to fetch branch data';
        console.error('Error fetching branch data:', err);
    } finally {
        loading.value = false;
    }
};

const handleSearch = () => { currentPage.value = 1; };
const handlePageChange = (page: number) => { if (page >= 1 && page <= totalPages.value) currentPage.value = page; };

const handleAddBranch = () => {
    branchForm.value = {
        branch_name: '',
        branch_address: '',
        contactNumber: '',
        email: '',
        logo: '',
        image: '',
        map: '',
        facebook: '',
        instagram: '',
        operatingDays: [],
        openingTime: '',
        closingTime: '',
    };
    formErrors.value = {};
    showAddModal.value = true;
};

const handleEditBranch = (branchId: string) => {
    const branch = branchData.value?.branches.find(b => b.branch_id === branchId);
    if (branch) {
        branchForm.value = {
            branch_name: branch.branch_name || '',
            branch_address: branch.branch_address || '',
            contactNumber: branch.branch_contact || '',
            email: branch.branch_email || '',
            logo: branch.branch_logo || '',
            image: branch.branch_image || '',
            map: branch.branch_map || '',
            facebook: branch.branch_facebook || '',
            instagram: branch.branch_instagram || '',
            operatingDays: parseOperatingDays(branch.operating_days ?? null) || [],
            openingTime: branch.opening_time || '',
            closingTime: branch.closing_time || '',
        };
    }
    selectedBranch.value = branch || null;
    formErrors.value = {};
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    branchForm.value = {
        branch_name: '',
        branch_address: '',
        contactNumber: '',
        email: '',
        logo: '',
        image: '',
        map: '',
        facebook: '',
        instagram: '',
        operatingDays: [],
        openingTime: '',
        closingTime: '',
    };
    formErrors.value = {};
};

const closeAddModal = () => {
    showAddModal.value = false;
    branchForm.value = {
        branch_name: '',
        branch_address: '',
        contactNumber: '',
        email: '',
        logo: '',
        image: '',
        map: '',
        facebook: '',
        instagram: '',
        operatingDays: [],
        openingTime: '',
        closingTime: '',
    };
    formErrors.value = {};
};

// Validate form before submission
const validateForm = (): boolean => {
    formErrors.value = {};
    if (!branchForm.value.branch_name.trim()) formErrors.value.branch_name = 'Name is required';
    if (!branchForm.value.branch_address.trim()) formErrors.value.branch_address = 'Address is required';
    if (branchForm.value.openingTime && !/^\d{2}:\d{2}$/.test(formatTime(branchForm.value.openingTime))) formErrors.value.openingTime = 'Invalid time format';
    if (branchForm.value.closingTime && !/^\d{2}:\d{2}$/.test(formatTime(branchForm.value.closingTime))) formErrors.value.closingTime = 'Invalid time format';
    return Object.keys(formErrors.value).length === 0;
};

const saveBranch = async () => {
    if (!validateForm()) return;
    try {
        const payload = {
            branch_name: branchForm.value.branch_name,
            branch_address: branchForm.value.branch_address,
            contact_number: branchForm.value.contactNumber,
            email: branchForm.value.email,
            logo: branchForm.value.logo,
            image: branchForm.value.image,
            map: branchForm.value.map,
            facebook: branchForm.value.facebook,
            instagram: branchForm.value.instagram,
            operating_days: branchForm.value.operatingDays.length ? JSON.stringify(branchForm.value.operatingDays) : '[]',
            opening_time: branchForm.value.openingTime ? formatTime(branchForm.value.openingTime) : null,
            closing_time: branchForm.value.closingTime ? formatTime(branchForm.value.closingTime) : null,
        };
        console.log('Payload:', payload); // Debug log
        const response = await axios.put(`/dashboard/owner/api/branches/${selectedBranch.value?.branch_id}`, payload, {
            headers: {
                'X-CSRF-TOKEN': page.props.csrf_token as string
            }
        });
        await fetchBranchData();
        closeEditModal();
    } catch (err: any) {
        error.value = err.response?.data?.message || 'Failed to save branch';
        if (err.response?.status === 422) {
            formErrors.value = err.response.data.errors;
        }
        console.error('Error saving branch:', err);
    }
};

const addBranch = async () => {
    if (!validateForm()) return;
    try {
        const payload = {
            branch_name: branchForm.value.branch_name,
            branch_address: branchForm.value.branch_address,
            contact_number: branchForm.value.contactNumber,
            email: branchForm.value.email,
            logo: branchForm.value.logo,
            image: branchForm.value.image,
            map: branchForm.value.map,
            facebook: branchForm.value.facebook,
            instagram: branchForm.value.instagram,
            operating_days: branchForm.value.operatingDays.length ? JSON.stringify(branchForm.value.operatingDays) : '[]',
            opening_time: branchForm.value.openingTime ? formatTime(branchForm.value.openingTime) : null,
            closing_time: branchForm.value.closingTime ? formatTime(branchForm.value.closingTime) : null,
        };
        console.log('Payload:', payload); // Debug log
        await axios.post('/dashboard/owner/api/branches', payload, {
            headers: {
                'X-CSRF-TOKEN': page.props.csrf_token as string
            }
        });
        await fetchBranchData();
        closeAddModal();
    } catch (err: any) {
        error.value = err.response?.data?.message || 'Failed to add branch';
        if (err.response?.status === 422) {
            formErrors.value = err.response.data.errors;
        }
        console.error('Error adding branch:', err);
    }
};

onMounted(() => {
    fetchBranchData();
});
</script>

<template>
    <Head title="Branch Settings" />
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
                    @click="fetchBranchData" 
                    class="mt-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
                >
                    Try Again
                </button>
            </div>

            <!-- Main Content -->
            <div v-else-if="branchData" class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-2 gap-4" :class="{ 'blur-[1px]': showEditModal || showAddModal }">
                    <h1 class="text-3xl font-bold text-gray-900">Branch Settings</h1>
                    <div class="flex flex-1 justify-end items-center gap-2">
                        <input
                            v-model="searchQuery"
                            @input="handleSearch"
                            type="text"
                            placeholder="Search by branch_name, address, email..."
                            class="border border-gray-300 rounded px-4 py-2 w-64 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                        />
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-2 gap-4" :class="{ 'blur-[1px]': showEditModal || showAddModal }">
                    <div class="flex items-center gap-2">
                        <button
                            @click="handleAddBranch"
                            class="bg-darkGreen-900 text-white px-6 py-2 rounded font-semibold shadow hover:bg-darkGreen-800 transition"
                        >Add Branch</button>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow p-0 overflow-x-auto" :class="{ 'blur-[1px]': showEditModal || showAddModal }">
                    <table class="w-full min-w-[900px] border-separate border-spacing-0">
                        <thead>
                            <tr class="bg-darkGreen-900 text-white">
                                <th class="py-3 px-4 text-left font-semibold">Branch Name</th>
                                <th class="py-3 px-4 text-left font-semibold">Address</th>
                                <th class="py-3 px-4 text-left font-semibold">Contact</th>
                                <th class="py-3 px-4 text-left font-semibold">Email</th>
                                <th class="py-3 px-4 text-left font-semibold">Operating Days</th>
                                <th class="py-3 px-4 text-left font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="paginatedBranches.length === 0">
                                <td colspan="7" class="text-center py-8 text-gray-500 text-lg">No branches found.</td>
                            </tr>
                            <tr v-for="branch in paginatedBranches" :key="branch.branch_id" class="border-b last:border-b-0 hover:bg-gray-50">
                                <td class="py-3 px-4">{{ branch.branch_name || 'N/A' }}</td>
                                <td class="py-3 px-4">{{ branch.branch_address || 'N/A' }}</td>
                                <td class="py-3 px-4">{{ branch.branch_contact || 'N/A' }}</td>
                                <td class="py-3 px-4">{{ branch.branch_email || 'N/A' }}</td>
                                <td class="py-3 px-4">{{ branch.operating_days && branch.operating_days.length ? branch.operating_days.map(day => day.slice(0, 3)).join(' ') : 'N/A' }}</td>
                                <td class="py-3 px-4">
                                    <button
                                        @click="handleEditBranch(branch.branch_id)"
                                        class="bg-darkGreen-900 text-white px-4 py-1 rounded hover:bg-darkGreen-800 transition"
                                    >Edit</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mt-4 gap-4" :class="{ 'blur-[1px]': showEditModal || showAddModal }">
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

                <!-- Edit Branch Modal -->
                <div v-if="showEditModal" class="absolute inset-0 flex items-center justify-center z-50">
                    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Edit Branch Record</h2>
                        <div class="space-y-4">
                            <div v-if="Object.keys(formErrors).length > 0" class="bg-red-50 border border-red-200 rounded-lg p-2 mb-4">
                                <p v-for="(error, key) in formErrors" :key="key" class="text-red-800 text-sm">{{ error }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Branch Name</label>
                                    <input v-model="branchForm.branch_name" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                    <input v-model="branchForm.branch_address" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                                    <input v-model="branchForm.contactNumber" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                    <input v-model="branchForm.email" type="email" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Logo</label>
                                    <input v-model="branchForm.logo" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Image</label>
                                    <input v-model="branchForm.image" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Google Maps URL</label>
                                    <input v-model="branchForm.map" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Facebook</label>
                                    <input v-model="branchForm.facebook" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Instagram</label>
                                    <input v-model="branchForm.instagram" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Operating Days</label>
                                    <div class="grid grid-cols-4 gap-2">
                                        <div v-for="day in availableDays" :key="day" class="flex items-center">
                                            <input
                                                type="checkbox"
                                                :value="day"
                                                v-model="branchForm.operatingDays"
                                                class="h-4 w-4 text-darkGreen-900 focus:ring-darkGreen-900 border-gray-300 rounded"
                                            />
                                            <label class="ml-2 text-sm text-gray-700">{{ day.slice(0, 3) }}</label>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Opening Time</label>
                                    <input v-model="branchForm.openingTime" type="time" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Closing Time</label>
                                    <input v-model="branchForm.closingTime" type="time" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 mt-6">
                            <button @click="closeEditModal" class="px-4 py-2 text-gray-700 bg-gray-200 rounded hover:bg-gray-300 transition">Cancel</button>
                            <button @click="saveBranch" class="px-4 py-2 text-white bg-darkGreen-900 rounded hover:bg-darkGreen-800 transition">Save Changes</button>
                        </div>
                    </div>
                </div>

                <!-- Add Branch Modal -->
                <div v-if="showAddModal" class="absolute inset-0 flex items-center justify-center z-50">
                    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Add New Branch</h2>
                        <div class="space-y-4">
                            <div v-if="Object.keys(formErrors).length > 0" class="bg-red-50 border border-red-200 rounded-lg p-2 mb-4">
                                <p v-for="(error, key) in formErrors" :key="key" class="text-red-800 text-sm">{{ error }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Branch Name</label>
                                    <input v-model="branchForm.branch_name" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                    <input v-model="branchForm.branch_address" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                                    <input v-model="branchForm.contactNumber" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                    <input v-model="branchForm.email" type="email" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Logo</label>
                                    <input v-model="branchForm.logo" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Image</label>
                                    <input v-model="branchForm.image" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Google Maps URL</label>
                                    <input v-model="branchForm.map" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Facebook</label>
                                    <input v-model="branchForm.facebook" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Instagram</label>
                                    <input v-model="branchForm.instagram" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Operating Days</label>
                                    <div class="grid grid-cols-4 gap-2">
                                        <div v-for="day in availableDays" :key="day" class="flex items-center">
                                            <input
                                                type="checkbox"
                                                :value="day"
                                                v-model="branchForm.operatingDays"
                                                class="h-4 w-4 text-darkGreen-900 focus:ring-darkGreen-900 border-gray-300 rounded"
                                            />
                                            <label class="ml-2 text-sm text-gray-700">{{ day.slice(0, 3) }}</label>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Opening Time</label>
                                    <input v-model="branchForm.openingTime" type="time" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Closing Time</label>
                                    <input v-model="branchForm.closingTime" type="time" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 mt-6">
                            <button @click="closeAddModal" class="px-4 py-2 text-gray-700 bg-gray-200 rounded hover:bg-gray-300 transition">Cancel</button>
                            <button @click="addBranch" class="px-4 py-2 text-white bg-green-600 rounded hover:bg-green-700 transition">Add Branch</button>
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