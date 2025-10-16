<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { Search, Plus, Edit2, ChevronLeft, ChevronRight, X, MapPin, Mail, Phone, Clock, Facebook, Instagram } from 'lucide-vue-next';
import { debounce } from 'lodash';

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

interface PageProps {
    csrf_token?: string;
}

const page = usePage<{ props: PageProps }>();
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Branch Settings', href: '/clinic/branches' },
];

const searchQuery = ref('');
const currentPage = ref(1);
const itemsPerPage = ref(10);
const branchData = ref<BranchData | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);
const showModal = ref(false);
const isEditing = ref(false);
const selectedBranch = ref<Branch | null>(null);

const branchForm = ref({
    branch_name: '',
    branch_address: '',
    branch_contact: '',
    branch_email: '',
    branch_logo: '',
    branch_image: '',
    branch_map: '',
    branch_facebook: '',
    branch_instagram: '',
    operating_days: [] as string[],
    opening_time: '',
    closing_time: '',
});

const formErrors = ref<{ [key: string]: string }>({});
const availableDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

const paginatedBranches = computed(() => branchData.value?.branches || []);

const totalPages = computed(() => branchData.value?.pagination.totalPages || 1);

const parseOperatingDays = (days: string | string[] | null): string[] => {
    if (!days) return [];
    let parsedDays: string[];
    if (Array.isArray(days)) {
        parsedDays = days;
    } else {
        try {
            parsedDays = JSON.parse(days) as string[];
        } catch (e) {
            console.error('Failed to parse operating_days:', e);
            return [];
        }
    }
    return parsedDays.sort((a, b) => 
        availableDays.indexOf(a) - availableDays.indexOf(b)
    );
};

const formatTime = (time: string): string => {
    if (!time) return '';
    const [hours, minutes] = time.split(':');
    return `${parseInt(hours, 10).toString().padStart(2, '0')}:${minutes.slice(0, 2)}`;
};

const formatTimeTo12Hour = (time?: string | null): string => {
    if (!time) return '--:--';
    const [hoursPart, minutesPart] = time.split(':');
    const hours = Number(hoursPart) || 0;
    const minutes = Number(minutesPart) || 0;
    const period = hours >= 12 ? 'PM' : 'AM';
    const formattedHours = hours % 12 || 12;
    return `${formattedHours}:${minutes.toString().padStart(2, '0')} ${period}`;
};

const fetchBranchData = async () => {
    try {
        loading.value = true;
        const response = await axios.get('/dashboard/owner/api/branches', {
            params: {
                per_page: itemsPerPage.value,
                page: currentPage.value,
                search: searchQuery.value
            }
        });
        const branches = response.data.branches.map((branch: any) => ({
            ...branch,
            operating_days: parseOperatingDays(branch.operating_days)
        }));
        branchData.value = {
            branches,
            pagination: response.data.pagination
        };
        currentPage.value = response.data.pagination.currentPage;
    } catch (err) {
        error.value = 'Failed to fetch branch data';
        console.error('Error fetching branch data:', err);
    } finally {
        loading.value = false;
    }
};

const handleSearch = debounce(() => {
    currentPage.value = 1;
    fetchBranchData();
}, 300);

const handlePageChange = (page: number) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
        fetchBranchData();
    }
};

const handleAddBranch = () => {
    branchForm.value = {
        branch_name: '',
        branch_address: '',
        branch_contact: '',
        branch_email: '',
        branch_logo: '',
        branch_image: '',
        branch_map: '',
        branch_facebook: '',
        branch_instagram: '',
        operating_days: [],
        opening_time: '',
        closing_time: '',
    };
    formErrors.value = {};
    isEditing.value = false;
    showModal.value = true;
};

const handleEditBranch = (branchId: string) => {
    const branch = branchData.value?.branches.find(b => b.branch_id === branchId);
    if (branch) {
        branchForm.value = {
            branch_name: branch.branch_name || '',
            branch_address: branch.branch_address || '',
            branch_contact: branch.branch_contact || '',
            branch_email: branch.branch_email || '',
            branch_logo: branch.branch_logo || '',
            branch_image: branch.branch_image || '',
            branch_map: branch.branch_map || '',
            branch_facebook: branch.branch_facebook || '',
            branch_instagram: branch.branch_instagram || '',
            operating_days: parseOperatingDays(branch.operating_days ?? null),
            opening_time: branch.opening_time || '',
            closing_time: branch.closing_time || '',
        };
        selectedBranch.value = branch;
        formErrors.value = {};
        isEditing.value = true;
        showModal.value = true;
    }
};

const resetForm = () => {
    branchForm.value = {
        branch_name: '',
        branch_address: '',
        branch_contact: '',
        branch_email: '',
        branch_logo: '',
        branch_image: '',
        branch_map: '',
        branch_facebook: '',
        branch_instagram: '',
        operating_days: [],
        opening_time: '',
        closing_time: '',
    };
    formErrors.value = {};
};

const closeModal = () => {
    showModal.value = false;
    resetForm();
};

const validateForm = (): boolean => {
    formErrors.value = {};
    if (!branchForm.value.branch_name.trim()) formErrors.value.branch_name = 'Branch name is required';
    if (!branchForm.value.branch_address.trim()) formErrors.value.branch_address = 'Address is required';
    if (branchForm.value.branch_email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(branchForm.value.branch_email))
        formErrors.value.branch_email = 'Invalid email format';
    if (branchForm.value.branch_contact && !/^\+?[\d\s-]{7,}$/.test(branchForm.value.branch_contact))
        formErrors.value.branch_contact = 'Invalid contact number format';
    if (branchForm.value.opening_time && !/^\d{2}:\d{2}$/.test(formatTime(branchForm.value.opening_time)))
        formErrors.value.opening_time = 'Invalid opening time format';
    if (branchForm.value.closing_time && !/^\d{2}:\d{2}$/.test(formatTime(branchForm.value.closing_time)))
        formErrors.value.closing_time = 'Invalid closing time format';
    if (branchForm.value.branch_map && !/^https?:\/\/.*/.test(branchForm.value.branch_map))
        formErrors.value.branch_map = 'Invalid Google Maps URL';
    if (branchForm.value.branch_facebook && !/^https?:\/\/(www\.)?facebook\.com\/.*/.test(branchForm.value.branch_facebook))
        formErrors.value.branch_facebook = 'Invalid Facebook URL';
    if (branchForm.value.branch_instagram && !/^https?:\/\/(www\.)?instagram\.com\/.*/.test(branchForm.value.branch_instagram))
        formErrors.value.branch_instagram = 'Invalid Instagram URL';
    return Object.keys(formErrors.value).length === 0;
};

const saveBranch = async () => {
    if (!validateForm()) return;
    try {
        const payload = {
            branch_name: branchForm.value.branch_name,
            branch_address: branchForm.value.branch_address,
            branch_contact: branchForm.value.branch_contact || null,
            branch_email: branchForm.value.branch_email || null,
            branch_logo: branchForm.value.branch_logo || null,
            branch_image: branchForm.value.branch_image || null,
            branch_map: branchForm.value.branch_map || null,
            branch_facebook: branchForm.value.branch_facebook || null,
            branch_instagram: branchForm.value.branch_instagram || null,
            operating_days: branchForm.value.operating_days.length ? JSON.stringify(branchForm.value.operating_days) : null,
            opening_time: branchForm.value.opening_time ? formatTime(branchForm.value.opening_time) : null,
            closing_time: branchForm.value.closing_time ? formatTime(branchForm.value.closing_time) : null,
        };
        const headers = page.props.csrf_token ? { 'X-CSRF-TOKEN': String(page.props.csrf_token) } : undefined;
        await axios.put(`/dashboard/owner/api/branches/${selectedBranch.value?.branch_id}`, payload, { headers });
        await fetchBranchData();
        closeModal();
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
            branch_contact: branchForm.value.branch_contact || null,
            branch_email: branchForm.value.branch_email || null,
            branch_logo: branchForm.value.branch_logo || null,
            branch_image: branchForm.value.branch_image || null,
            branch_map: branchForm.value.branch_map || null,
            branch_facebook: branchForm.value.branch_facebook || null,
            branch_instagram: branchForm.value.branch_instagram || null,
            operating_days: branchForm.value.operating_days.length ? JSON.stringify(branchForm.value.operating_days) : null,
            opening_time: branchForm.value.opening_time ? formatTime(branchForm.value.opening_time) : null,
            closing_time: branchForm.value.closing_time ? formatTime(branchForm.value.closing_time) : null,
        };
        const headers = page.props.csrf_token ? { 'X-CSRF-TOKEN': String(page.props.csrf_token) } : undefined;
        await axios.post('/dashboard/owner/api/branches', payload, { headers });
        await fetchBranchData();
        closeModal();
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
        <div class="min-h-screen bg-gray-50 dark:bg-neutral-900 transition-colors duration-300 rounded-xl mt-2 p-4">
            <div class="px-4 py-4 mx-auto">
                <!-- Loading State -->
                <div v-if="loading" class="text-center py-16 text-gray-600 dark:text-neutral-300">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-t-[#1e4f4f] border-r-[#1e4f4f] border-b-transparent border-l-transparent"></div>
                    <p class="mt-4 text-lg">Loading branches...</p>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="text-center py-16 rounded-2xl bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-300">
                    <p class="text-lg mb-4">{{ error }}</p>
                    <button 
                        @click="fetchBranchData" 
                        class="px-5 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium"
                    >
                        Try Again
                    </button>
                </div>

                <!-- Main Content -->
                <div v-else-if="branchData">
                    <!-- Header Section -->
                    <div class="mb-8 flex items-center justify-between" :class="{ 'blur-sm': showModal }">
                        <div>
                            <h1 class="text-3xl font-bold mb-2 text-gray-900 dark:text-white">Branch Management</h1>
                            <p class="text-lg text-gray-600 dark:text-neutral-400">Manage your clinic locations and settings</p>
                        </div>
                    </div>

                    <!-- Controls Section -->
                    <div class="mb-6 space-y-4" :class="{ 'blur-sm': showModal }">
                        <!-- Search and Add Button -->
                        <div class="flex flex-col lg:flex-row gap-4 items-stretch lg:items-center justify-between">
                            <div class="relative flex-1 max-w-xl">
                                <Search class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-neutral-500" :size="20" />
                                <input
                                    v-model="searchQuery"
                                    @input="handleSearch"
                                    type="text"
                                    placeholder="Search by name, address, or email..."
                                    class="w-full pl-12 pr-4 py-3.5 rounded-xl transition-all duration-200 border-2 focus:outline-none focus:ring-4 bg-white dark:bg-neutral-800 border-gray-200 dark:border-neutral-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-neutral-500 focus:border-[#1e4f4f] focus:ring-[#1e4f4f]/20"
                                />
                            </div>
                            <button
                                @click="handleAddBranch"
                                class="bg-[#1e4f4f] hover:bg-[#2d5f5c] text-white px-6 py-3.5 rounded-xl font-semibold shadow-lg transition-all duration-200 flex items-center justify-center gap-2"
                            >
                                <Plus :size="20" />
                                Add Branch
                            </button>
                        </div>

                        <!-- Filters -->
                        <div class="flex flex-wrap gap-3 p-4 rounded-xl bg-white dark:bg-neutral-800/50">
                            <div class="flex items-center gap-3">
                                <label class="text-sm font-medium text-gray-600 dark:text-neutral-400">Show:</label>
                                <select
                                    v-model="itemsPerPage"
                                    @change="fetchBranchData"
                                    class="px-4 py-2 rounded-lg font-medium transition-all duration-200 border focus:outline-none focus:ring-2 bg-gray-50 dark:bg-neutral-700 border-gray-300 dark:border-neutral-600 text-gray-900 dark:text-white focus:border-[#1e4f4f] focus:ring-[#1e4f4f]/30"
                                >
                                    <option value="6">6</option>
                                    <option value="12">12</option>
                                    <option value="24">24</option>
                                    <option value="48">48</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Branch Cards Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-6" :class="{ 'blur-sm': showModal }">
                        <div v-if="paginatedBranches.length === 0" class="col-span-full">
                            <div class="text-center py-20 rounded-2xl bg-white dark:bg-neutral-800 text-gray-500 dark:text-neutral-400">
                                <p class="text-xl">No branches found.</p>
                            </div>
                        </div>
                        <div 
                            v-for="branch in paginatedBranches" 
                            :key="branch.branch_id" 
                            class="group relative rounded-xl p-6 transition-all duration-300 hover:scale-[1.02] cursor-pointer shadow-md bg-white dark:bg-neutral-800 hover:shadow-lg dark:hover:bg-neutral-750 border border-gray-100 dark:border-neutral-700"
                        >
                            <div class="absolute top-4 right-4">
                                <button
                                    @click="handleEditBranch(branch.branch_id)"
                                    class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg font-medium transition-all duration-200 bg-[#1e4f4f] hover:bg-[#2d5f5c] text-white"
                                    title="Edit branch"
                                >
                                    <Edit2 :size="16" />
                                    Edit
                                </button>
                            </div>
                            
                            <div class="mb-4">
                                <h3 class="font-bold text-lg mb-3 line-clamp-1 text-gray-900 dark:text-white pr-20">
                                    {{ branch.branch_name || 'N/A' }}
                                </h3>
                                
                                <!-- Address -->
                                <div class="flex items-start gap-3 mb-3">
                                    <MapPin class="text-[#1e4f4f] flex-shrink-0 mt-0.5" :size="18" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase tracking-wide mb-1">Address</p>
                                        <p class="text-sm text-gray-900 dark:text-white break-words">{{ branch.branch_address || 'Not specified' }}</p>
                                    </div>
                                </div>

                                <!-- Contact -->
                                <div class="flex items-start gap-3 mb-3">
                                    <Phone class="text-[#1e4f4f] flex-shrink-0 mt-0.5" :size="18" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase tracking-wide mb-1">Contact</p>
                                        <p class="text-sm text-gray-900 dark:text-white">{{ branch.branch_contact || 'Not specified' }}</p>
                                    </div>
                                </div>

                                <!-- Email -->
                                <div class="flex items-start gap-3 mb-4">
                                    <Mail class="text-[#1e4f4f] flex-shrink-0 mt-0.5" :size="18" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase tracking-wide mb-1">Email</p>
                                        <p class="text-sm text-gray-900 dark:text-white truncate">{{ branch.branch_email || 'Not specified' }}</p>
                                    </div>
                                </div>

                                <!-- Operating Hours -->
                                <div class="pt-3 border-t border-gray-100 dark:border-neutral-700">
                                    <div class="flex items-center gap-2 mb-2">
                                        <Clock class="text-[#1e4f4f]" :size="16" />
                                        <p class="text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase tracking-wide">Operating Hours</p>
                                    </div>
                                    <div v-if="branch.operating_days?.length" class="space-y-2">
                                        <div class="flex flex-wrap gap-1.5">
                                            <span 
                                                v-for="day in branch.operating_days" 
                                                :key="day"
                                                class="inline-block bg-[#1e4f4f]/10 dark:bg-[#1e4f4f]/20 text-[#1e4f4f] px-2.5 py-1 rounded text-xs font-medium"
                                            >
                                                {{ day.slice(0, 3) }}
                                            </span>
                                        </div>
                                        <p v-if="branch.opening_time || branch.closing_time" class="text-sm text-gray-600 dark:text-neutral-400">
                                            {{ formatTimeTo12Hour(branch.opening_time) }} - {{ formatTimeTo12Hour(branch.closing_time) }}
                                        </p>
                                    </div>
                                    <p v-else class="text-sm text-gray-500 dark:text-neutral-400">Not specified</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 p-4 rounded-xl bg-white dark:bg-neutral-800" :class="{ 'blur-sm': showModal }">
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-medium text-gray-600 dark:text-neutral-400">
                                Showing {{ (currentPage - 1) * itemsPerPage + 1 }} to 
                                {{ Math.min(currentPage * itemsPerPage, branchData?.pagination.totalRecords || 0) }} of 
                                {{ branchData?.pagination.totalRecords || 0 }} branches
                            </span>
                        </div>
                        <div class="flex items-center gap-3">
                            <button
                                @click="handlePageChange(currentPage - 1)"
                                :disabled="currentPage === 1"
                                class="p-2 rounded-lg transition-all duration-200 disabled:opacity-40 disabled:cursor-not-allowed bg-gray-100 dark:bg-neutral-700 hover:bg-gray-200 dark:hover:bg-neutral-600 text-gray-900 dark:text-white disabled:hover:bg-gray-100 dark:disabled:hover:bg-neutral-700"
                            >
                                <ChevronLeft :size="20" />
                            </button>
                            <span class="text-sm font-medium px-4 text-gray-700 dark:text-neutral-300">
                                Page {{ totalPages === 0 ? 0 : currentPage }} of {{ totalPages === 0 ? 0 : totalPages }}
                            </span>
                            <button
                                @click="handlePageChange(currentPage + 1)"
                                :disabled="currentPage === totalPages || totalPages === 0"
                                class="p-2 rounded-lg transition-all duration-200 disabled:opacity-40 disabled:cursor-not-allowed bg-gray-100 dark:bg-neutral-700 hover:bg-gray-200 dark:hover:bg-neutral-600 text-gray-900 dark:text-white disabled:hover:bg-gray-100 dark:disabled:hover:bg-neutral-700"
                            >
                                <ChevronRight :size="20" />
                            </button>
                        </div>
                    </div>

                    <!-- Modal -->
                    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                        <div class="w-full max-w-3xl rounded-2xl shadow-2xl max-h-[90vh] overflow-y-auto bg-white dark:bg-neutral-800">
                            <div class="sticky top-0 z-10 px-6 py-5 border-b backdrop-blur-xl bg-white/95 dark:bg-neutral-800/95 border-gray-200 dark:border-neutral-700">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ isEditing ? 'Edit Branch' : 'Add New Branch' }}</h2>
                                        <p class="text-gray-600 dark:text-neutral-400 mt-1">{{ isEditing ? 'Update branch details' : 'Create a new clinic location' }}</p>
                                    </div>
                                    <button @click="closeModal" class="text-gray-400 dark:text-neutral-500 hover:text-gray-900 dark:hover:text-white transition p-2 hover:bg-gray-100 dark:hover:bg-neutral-700 rounded-lg">
                                        <X :size="24" />
                                    </button>
                                </div>
                            </div>

                            <!-- Modal Body -->
                            <div class="p-6">
                                <!-- Error Messages -->
                                <div v-if="Object.keys(formErrors).length > 0" class="mb-6 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-600 rounded-lg p-4">
                                    <h4 class="text-sm font-semibold text-red-900 dark:text-red-200 mb-2">Please fix the following errors:</h4>
                                    <ul class="space-y-1">
                                        <li v-for="(error, key) in formErrors" :key="key" class="text-sm text-red-800 dark:text-red-300">• {{ error }}</li>
                                    </ul>
                                </div>

                                <!-- Form Fields -->
                                <div class="space-y-5">
                                    <!-- Basic Information -->
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-200 dark:border-neutral-800">Basic Information</h3>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2">Branch Name *</label>
                                                <input v-model="branchForm.branch_name" type="text" class="w-full px-4 py-3 rounded-xl transition-all duration-200 border focus:outline-none focus:ring-2 bg-white dark:bg-neutral-750 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white focus:border-[#1e4f4f] focus:ring-[#1e4f4f]/30" />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2">Email</label>
                                                <input v-model="branchForm.branch_email" type="email" class="w-full px-4 py-3 rounded-xl transition-all duration-200 border focus:outline-none focus:ring-2 bg-white dark:bg-neutral-750 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white focus:border-[#1e4f4f] focus:ring-[#1e4f4f]/30" />
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2">Address *</label>
                                                <input v-model="branchForm.branch_address" type="text" class="w-full px-4 py-3 rounded-xl transition-all duration-200 border focus:outline-none focus:ring-2 bg-white dark:bg-neutral-750 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white focus:border-[#1e4f4f] focus:ring-[#1e4f4f]/30" />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2">Contact Number</label>
                                                <input v-model="branchForm.branch_contact" type="text" class="w-full px-4 py-3 rounded-xl transition-all duration-200 border focus:outline-none focus:ring-2 bg-white dark:bg-neutral-750 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white focus:border-[#1e4f4f] focus:ring-[#1e4f4f]/30" />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Operating Hours -->
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-200 dark:border-neutral-800">Operating Hours</h3>
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-3">Operating Days</label>
                                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                                    <label v-for="day in availableDays" :key="day" class="flex items-center gap-2 cursor-pointer group">
                                                        <input
                                                            type="checkbox"
                                                            :value="day"
                                                            v-model="branchForm.operating_days"
                                                            class="h-4 w-4 text-[#1e4f4f] focus:ring-[#1e4f4f] border-gray-300 dark:border-neutral-600 rounded cursor-pointer accent-[#1e4f4f]"
                                                        />
                                                        <span class="text-sm font-medium text-gray-700 dark:text-neutral-300 group-hover:text-[#1e4f4f] transition">{{ day }}</span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2">Opening Time</label>
                                                    <input v-model="branchForm.opening_time" type="time" class="w-full px-4 py-3 rounded-xl transition-all duration-200 border focus:outline-none focus:ring-2 bg-white dark:bg-neutral-750 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white focus:border-[#1e4f4f] focus:ring-[#1e4f4f]/30" />
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2">Closing Time</label>
                                                    <input v-model="branchForm.closing_time" type="time" class="w-full px-4 py-3 rounded-xl transition-all duration-200 border focus:outline-none focus:ring-2 bg-white dark:bg-neutral-750 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white focus:border-[#1e4f4f] focus:ring-[#1e4f4f]/30" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Media & Links -->
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-200 dark:border-neutral-800">Media & Links</h3>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2">Logo URL</label>
                                                <input v-model="branchForm.branch_logo" type="text" placeholder="https://example.com/logo.png" class="w-full px-4 py-3 rounded-xl transition-all duration-200 border focus:outline-none focus:ring-2 bg-white dark:bg-neutral-750 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-neutral-500 focus:border-[#1e4f4f] focus:ring-[#1e4f4f]/30" />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2">Branch Image URL</label>
                                                <input v-model="branchForm.branch_image" type="text" placeholder="https://example.com/branch.jpg" class="w-full px-4 py-3 rounded-xl transition-all duration-200 border focus:outline-none focus:ring-2 bg-white dark:bg-neutral-750 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-neutral-500 focus:border-[#1e4f4f] focus:ring-[#1e4f4f]/30" />
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2">Google Maps URL</label>
                                                <input v-model="branchForm.branch_map" type="text" placeholder="https://maps.google.com/..." class="w-full px-4 py-3 rounded-xl transition-all duration-200 border focus:outline-none focus:ring-2 bg-white dark:bg-neutral-750 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-neutral-500 focus:border-[#1e4f4f] focus:ring-[#1e4f4f]/30" />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Social Media -->
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-200 dark:border-neutral-800">Social Media</h3>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2 flex items-center gap-2">
                                                    <Facebook :size="16" class="text-[#1e4f4f]" />
                                                    Facebook
                                                </label>
                                                <input v-model="branchForm.branch_facebook" type="text" placeholder="https://facebook.com/..." class="w-full px-4 py-3 rounded-xl transition-all duration-200 border focus:outline-none focus:ring-2 bg-white dark:bg-neutral-750 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-neutral-500 focus:border-[#1e4f4f] focus:ring-[#1e4f4f]/30" />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2 flex items-center gap-2">
                                                    <Instagram :size="16" class="text-[#1e4f4f]" />
                                                    Instagram
                                                </label>
                                                <input v-model="branchForm.branch_instagram" type="text" placeholder="https://instagram.com/..." class="w-full px-4 py-3 rounded-xl transition-all duration-200 border focus:outline-none focus:ring-2 bg-white dark:bg-neutral-750 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-neutral-500 focus:border-[#1e4f4f] focus:ring-[#1e4f4f]/30" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Footer -->
                            <div class="sticky bottom-0 px-6 py-4 border-t backdrop-blur-xl bg-white/95 dark:bg-neutral-800/95 border-gray-200 dark:border-neutral-700">
                                <div class="flex justify-end gap-3">
                                    <button 
                                        @click="closeModal" 
                                        class="px-6 py-2.5 rounded-xl font-semibold transition-all duration-200 bg-gray-100 dark:bg-neutral-700 hover:bg-gray-200 dark:hover:bg-neutral-600 text-gray-900 dark:text-white"
                                    >
                                        Cancel
                                    </button>
                                    <button 
                                        @click="isEditing ? saveBranch() : addBranch()" 
                                        class="px-6 py-2.5 rounded-xl font-semibold transition-all duration-200 bg-[#1e4f4f] hover:bg-[#2d5f5c] text-white"
                                    >
                                        {{ isEditing ? 'Save Changes' : 'Add Branch' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>