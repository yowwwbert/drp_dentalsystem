<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import axios from 'axios';
import { Search, Filter, Plus, Eye, Pencil, Clock, Tag, Activity, ChevronLeft, ChevronRight } from 'lucide-vue-next';

// provide a typed shape for page props so csrf_token is recognized as string | undefined
const page = usePage<{ csrf_token?: string }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Services List', href: '/clinic/services' },
];

const searchQuery = ref('');
const selectedStatus = ref('All Status');
const selectedCategory = ref('All Categories');
const currentPage = ref(1);
const itemsPerPage = ref(9);

const categories = ['General', 'Restorative', 'Cosmetic', 'Surgical', 'Preventive', 'Orthodontic', 'Pediatric', 'Endodontic', 'Periodontic'];

const showViewModal = ref(false);
const showEditModal = ref(false);
const isEditing = ref(false);
const selectedService = ref<any>(null);

const serviceForm = ref({
    title: '',
    category: 'General',
    description: '',
    price: '',
    duration: '',
    status: 'Active',
});

const treatments = ref<any[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);

// Fetch treatments from API
const fetchTreatments = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get('/dashboard/clinic/api/treatments');
        treatments.value = Array.isArray(response.data) 
            ? response.data.map((treatment: any) => ({
                  id: treatment.treatment_id,
                  title: treatment.treatment_name,
                  category: treatment.treatment_type || 'General',
                  duration: treatment.treatment_duration || 'N/A',
                  description: treatment.treatment_description || 'No description available',
                  price: treatment.treatment_cost || '₱0',
                  status: treatment.is_active ? 'Active' : 'Inactive',
                  icon: treatment.icon || '/default-icon.png',
              }))
            : [];
    } catch (err: any) {
        console.error('Error fetching treatments:', err);
        error.value = err.response?.status === 404 
            ? 'No treatments found.'
            : 'Failed to load treatments. Please try again.';
    } finally {
        loading.value = false;
    }
};

fetchTreatments();

const filteredServices = computed(() => {
    let filtered = treatments.value;
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(service =>
            service.title.toLowerCase().includes(query) ||
            service.category.toLowerCase().includes(query) ||
            (service.description?.toLowerCase() || '').includes(query)
        );
    }
    if (selectedStatus.value !== 'All Status') {
        filtered = filtered.filter(service => service.status === selectedStatus.value);
    }
    if (selectedCategory.value !== 'All Categories') {
        filtered = filtered.filter(service => service.category === selectedCategory.value);
    }
    return filtered;
});

const paginatedServices = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredServices.value.slice(start, end);
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredServices.value.length / itemsPerPage.value)));

const handleSearch = () => { currentPage.value = 1; };
const handleStatusFilter = (status: string) => { selectedStatus.value = status; currentPage.value = 1; };
const handleCategoryFilter = (category: string) => { selectedCategory.value = category; currentPage.value = 1; };
const handlePageChange = (page: number) => { if (page >= 1 && page <= totalPages.value) currentPage.value = page; };

const handleViewService = (serviceId: string) => {
    const service = treatments.value.find(s => s.id === serviceId);
    selectedService.value = service || null;
    showViewModal.value = true;
};

const handleAddService = () => {
    isEditing.value = false;
    serviceForm.value = {
        title: '',
        category: 'General',
        description: '',
        price: '',
        duration: '',
        status: 'Active',
    };
    showEditModal.value = true;
};

const handleEditService = (serviceId: string) => {
    const service = treatments.value.find(s => s.id === serviceId);
    if (service) {
        selectedService.value = service;
        serviceForm.value = {
            title: service.title,
            category: service.category,
            description: service.description,
            price: service.price,
            duration: service.duration,
            status: service.status,
        };
        isEditing.value = true;
        showEditModal.value = true;
    } else {
        error.value = 'Service not found.';
    }
};

const closeViewModal = () => {
    showViewModal.value = false;
    selectedService.value = null;
};

const closeEditModal = () => {
    showEditModal.value = false;
    isEditing.value = false;
    selectedService.value = null;
    serviceForm.value = {
        title: '',
        category: 'General',
        description: '',
        price: '',
        duration: '',
        status: 'Active',
    };
};

// Validate duration to accept only numbers or "X mins"
const validateDuration = (value: string) => {
    const numericValue = value.replace(/[^0-9]/g, '');
    return numericValue ? `${numericValue} mins` : '';
};

const addService = async () => {
    if (!serviceForm.value.title || !serviceForm.value.duration) {
        error.value = 'Title and duration are required.';
        return;
    }
    const cleanedDuration = validateDuration(serviceForm.value.duration);
    if (!cleanedDuration && serviceForm.value.duration) {
        error.value = 'Duration must be a number (e.g., 30 mins).';
        return;
    }
    try {
        await axios.post('/dashboard/clinic/api/treatments', {
            treatment_name: serviceForm.value.title,
            treatment_type: serviceForm.value.category,
            treatment_duration: cleanedDuration ? parseInt(cleanedDuration) : null,
            treatment_description: serviceForm.value.description,
            treatment_cost: serviceForm.value.price.replace(/[^0-9]/g, ''),
            is_active: serviceForm.value.status === 'Active',
        });
        await fetchTreatments();
        closeEditModal();
    } catch (err: any) {
        console.error('Error adding service:', err);
        error.value = err.response?.data?.message || 'Failed to add service. Please try again.';
    }
};

const saveService = async () => {
    if (!selectedService.value || !selectedService.value.id) {
        error.value = 'No service selected for editing.';
        return;
    }
    if (!serviceForm.value.title || !serviceForm.value.duration) {
        error.value = 'Title and duration are required.';
        return;
    }
    const cleanedDuration = validateDuration(serviceForm.value.duration);
    if (!cleanedDuration && serviceForm.value.duration) {
        error.value = 'Duration must be a number (e.g., 30 mins).';
        return;
    }
    try {
        await axios.put(`/dashboard/clinic/api/treatments/${selectedService.value.id}`, {
            treatment_name: serviceForm.value.title,
            treatment_type: serviceForm.value.category,
            treatment_duration: cleanedDuration ? parseInt(cleanedDuration) : null,
            treatment_description: serviceForm.value.description,
            treatment_cost: serviceForm.value.price.replace(/[^0-9]/g, ''),
            is_active: serviceForm.value.status === 'Active',
        });
        await fetchTreatments();
        closeEditModal();
    } catch (err: any) {
        console.error('Error saving service:', err);
        error.value = err.response?.data?.message || 'Failed to save service. Please try again.';
    }
};
</script>

<template>
    <Head title="Services List">
        <meta name="csrf-token" :content="page.props.csrf_token" />
    </Head>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gray-50 dark:bg-neutral-900 transition-colors duration-300 rounded-xl mt-2 p-4">
            <div class="px-4 py-4 mx-auto">
                <!-- Loading and Error States -->
                <div v-if="loading" class="text-center py-16 text-gray-600 dark:text-neutral-300">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-t-[#1e4f4f] border-r-[#1e4f4f] border-b-transparent border-l-transparent"></div>
                    <p class="mt-4 text-lg">Loading treatments...</p>
                </div>
                <div v-else-if="error" class="text-center py-16 rounded-2xl bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-300">
                    <p class="text-lg">{{ error }}</p>
                </div>

                <div v-else>
                    <!-- Header Section -->
                    <div class="mb-8 flex items-center justify-between" :class="{ 'blur-sm': showViewModal || showEditModal }">
                        <div>
                            <h1 class="text-3xl font-bold mb-2 text-gray-900 dark:text-white">
                                Dental Services
                            </h1>
                            <p class="text-lg text-gray-600 dark:text-neutral-400">
                                Manage your clinic's treatment offerings
                            </p>
                        </div>
                    </div>

                    <!-- Controls Section -->
                    <div class="mb-6 space-y-4" :class="{ 'blur-sm': showViewModal || showEditModal }">
                        <!-- Search and Add Button -->
                        <div class="flex flex-col lg:flex-row gap-4 items-stretch lg:items-center justify-between">
                            <div class="relative flex-1 max-w-xl">
                                <Search class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-neutral-500" :size="20" />
                                <input
                                    v-model="searchQuery"
                                    @input="handleSearch"
                                    type="text"
                                    placeholder="Search services..."
                                    class="w-full pl-12 pr-4 py-3.5 rounded-xl transition-all duration-200 border-2 focus:outline-none focus:ring-4 bg-white dark:bg-neutral-800 border-gray-200 dark:border-neutral-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-neutral-500 focus:border-[#1e4f4f] focus:ring-[#1e4f4f]/20"
                                />
                            </div>
                            <button
                                @click="handleAddService"
                                class="bg-[#1e4f4f] hover:bg-[#2d5f5c] text-white px-6 py-3.5 rounded-xl font-semibold shadow-lg transition-all duration-200 flex items-center justify-center gap-2"
                            >
                                <Plus :size="20" />
                                Add Service
                            </button>
                        </div>

                        <!-- Filters -->
                        <div class="flex flex-wrap gap-3 p-4 rounded-xl bg-white dark:bg-neutral-800/50">
                            <div class="flex items-center gap-2">
                                <Activity class="text-gray-500 dark:text-neutral-400" :size="18" />
                                <select
                                    v-model="selectedStatus"
                                    @change="handleStatusFilter(selectedStatus)"
                                    class="px-4 py-2 rounded-lg font-medium transition-all duration-200 border focus:outline-none focus:ring-2 bg-gray-50 dark:bg-neutral-700 border-gray-300 dark:border-neutral-600 text-gray-900 dark:text-white focus:border-[#1e4f4f] focus:ring-[#1e4f4f]/30"
                                >
                                    <option>All Status</option>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="flex items-center gap-2">
                                <Filter class="text-gray-500 dark:text-neutral-400" :size="18" />
                                <select
                                    v-model="selectedCategory"
                                    @change="handleCategoryFilter(selectedCategory)"
                                    class="px-4 py-2 rounded-lg font-medium transition-all duration-200 border focus:outline-none focus:ring-2 bg-gray-50 dark:bg-neutral-700 border-gray-300 dark:border-neutral-600 text-gray-900 dark:text-white focus:border-[#1e4f4f] focus:ring-[#1e4f4f]/30"
                                >
                                    <option>All Categories</option>
                                    <option v-for="category in categories" :key="category" :value="category">{{ category }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Services Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-6" :class="{ 'blur-sm': showViewModal || showEditModal }">
                        <div v-if="paginatedServices.length === 0" class="col-span-full">
                            <div class="text-center py-20 rounded-2xl bg-white dark:bg-neutral-800 text-gray-500 dark:text-neutral-400">
                                <p class="text-xl">No services found.</p>
                            </div>
                        </div>
                        <div
                            v-for="service in paginatedServices"
                            :key="service.id"
                            class="group relative rounded-xl p-6 transition-all duration-300 hover:scale-[1.02] cursor-pointer shadow-md bg-white dark:bg-neutral-800 hover:shadow-lg dark:hover:bg-neutral-750 border border-gray-100 dark:border-neutral-700"
                        >
                            <div class="absolute top-4 right-4">
                                <span :class="[
                                    'px-3 py-1 rounded-full text-xs font-semibold flex items-center gap-1.5',
                                    service.status === 'Active'
                                        ? 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300'
                                        : 'bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300',
                                ]">
                                    <span :class="['w-1.5 h-1.5 rounded-full', service.status === 'Active' ? 'bg-emerald-400' : 'bg-red-400']"></span>
                                    {{ service.status }}
                                </span>
                            </div>
                            <div class="mb-4">
                                <div class="flex items-start gap-3 mb-3">
                                    <div class="flex-1">
                                        <h3 class="font-bold text-lg mb-1 line-clamp-1 text-gray-900 dark:text-white">
                                            {{ service.title }}
                                        </h3>
                                        <div class="flex items-center gap-2">
                                            <Tag :size="14" class="text-gray-400 dark:text-neutral-500" />
                                            <span class="text-sm font-medium text-gray-600 dark:text-neutral-400">
                                                {{ service.category }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-sm line-clamp-2 mb-4 text-gray-600 dark:text-neutral-400">
                                    {{ service.description }}
                                </p>
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-lg text-[#1e4f4f]">
                                            {{ service.price }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Clock :size="16" class="text-gray-400 dark:text-neutral-500" />
                                        <span class="text-sm font-medium text-gray-600 dark:text-neutral-400">
                                            {{ service.duration }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex gap-2 pt-4 border-t border-gray-100 dark:border-neutral-700">
                                <button
                                    @click="handleViewService(service.id)"
                                    class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg font-medium transition-all duration-200 bg-gray-100 dark:bg-neutral-700 hover:bg-gray-200 dark:hover:bg-neutral-600 text-gray-900 dark:text-white"
                                >
                                    <Eye :size="16" />
                                    View
                                </button>
                                <button
                                    @click="handleEditService(service.id)"
                                    class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg font-medium transition-all duration-200 bg-[#1e4f4f] hover:bg-[#2d5f5c] text-white"
                                >
                                    <Pencil :size="16" />
                                    Edit
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 p-4 rounded-xl bg-white dark:bg-neutral-800" :class="{ 'blur-sm': showViewModal || showEditModal }">
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-medium text-gray-600 dark:text-neutral-400">
                                Show
                            </span>
                            <select
                                v-model="itemsPerPage"
                                @change="currentPage = 1"
                                class="px-3 py-2 rounded-lg font-medium transition-all duration-200 border focus:outline-none bg-gray-50 dark:bg-neutral-700 border-gray-300 dark:border-neutral-600 text-gray-900 dark:text-white"
                            >
                                <option value="6">6</option>
                                <option value="9">9</option>
                                <option value="12">12</option>
                                <option value="24">24</option>
                            </select>
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

                    <!-- View Modal -->
                    <div v-if="showViewModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                        <div class="w-full max-w-3xl rounded-2xl shadow-2xl max-h-[90vh] overflow-y-auto bg-white dark:bg-neutral-800">
                            <div class="sticky top-0 z-10 px-6 py-5 border-b backdrop-blur-xl bg-white/95 dark:bg-neutral-800/95 border-gray-200 dark:border-neutral-700">
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                                    Service Details
                                </h2>
                            </div>
                            <div v-if="selectedService" class="p-6 space-y-6">
                                <div class="flex items-start gap-4">
                                    <div class="flex-1">
                                        <h3 class="text-2xl font-bold mb-2 text-gray-900 dark:text-white">
                                            {{ selectedService.title }}
                                        </h3>
                                        <div class="flex items-center gap-3">
                                            <span class="px-3 py-1 rounded-full text-sm font-medium bg-[#1e4f4f]/20 dark:bg-[#1e4f4f]/30 text-[#1e4f4f]">
                                                {{ selectedService.category }}
                                            </span>
                                            <span :class="[
                                                'px-3 py-1 rounded-full text-sm font-semibold',
                                                selectedService.status === 'Active'
                                                    ? 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300'
                                                    : 'bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300',
                                            ]">
                                                {{ selectedService.status }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-4 rounded-xl bg-gray-50 dark:bg-neutral-750">
                                    <label class="block text-sm font-semibold mb-2 text-gray-700 dark:text-neutral-300">
                                        Description
                                    </label>
                                    <p class="text-sm leading-relaxed text-gray-600 dark:text-neutral-400">
                                        {{ selectedService.description }}
                                    </p>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-neutral-750">
                                        <div class="flex items-center gap-2 mb-2">
                                            <Tag :size="16" class="text-[#1e4f4f]" />
                                            <label class="text-sm font-semibold text-gray-700 dark:text-neutral-300">
                                                ID
                                            </label>
                                        </div>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ selectedService.id }}
                                        </p>
                                    </div>
                                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-neutral-750">
                                        <div class="flex items-center gap-2 mb-2">
                                            <Tag :size="16" class="text-[#1e4f4f]" />
                                            <label class="text-sm font-semibold text-gray-700 dark:text-neutral-300">
                                                Price
                                            </label>
                                        </div>
                                        <p class="font-bold text-lg text-[#1e4f4f]">
                                            {{ selectedService.price }}
                                        </p>
                                    </div>
                                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-neutral-750">
                                        <div class="flex items-center gap-2 mb-2">
                                            <Clock :size="16" class="text-[#1e4f4f]" />
                                            <label class="text-sm font-semibold text-gray-700 dark:text-neutral-300">
                                                Duration
                                            </label>
                                        </div>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ selectedService.duration }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="sticky bottom-0 px-6 py-4 border-t backdrop-blur-xl bg-white/95 dark:bg-neutral-800/95 border-gray-200 dark:border-neutral-700">
                                <div class="flex justify-end">
                                    <button
                                        @click="closeViewModal"
                                        class="px-6 py-2.5 rounded-xl font-semibold transition-all duration-200 bg-gray-100 dark:bg-neutral-700 hover:bg-gray-200 dark:hover:bg-neutral-600 text-gray-900 dark:text-white"
                                    >
                                        Close
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Edit/Add Modal -->
                    <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                        <div class="w-full max-w-3xl rounded-2xl shadow-2xl max-h-[90vh] overflow-y-auto bg-white dark:bg-neutral-800">
                            <div class="sticky top-0 z-10 px-6 py-5 border-b backdrop-blur-xl bg-white/95 dark:bg-neutral-800/95 border-gray-200 dark:border-neutral-700">
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ isEditing ? 'Edit Service' : 'Add New Service' }}
                                </h2>
                            </div>
                            <div class="p-6 space-y-6">
                                <div>
                                    <label class="block text-sm font-semibold mb-2 text-gray-700 dark:text-neutral-300">
                                        Service Title <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="serviceForm.title"
                                        type="text"
                                        placeholder="Enter service title"
                                        class="w-full px-4 py-3 rounded-xl transition-all duration-200 border focus:outline-none focus:ring-2 bg-white dark:bg-neutral-750 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-neutral-500 focus:border-[#1e4f4f] focus:ring-[#1e4f4f]/30"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-2 text-gray-700 dark:text-neutral-300">
                                        Category
                                    </label>
                                    <select
                                        v-model="serviceForm.category"
                                        class="w-full px-4 py-3 rounded-xl transition-all duration-200 border focus:outline-none focus:ring-2 bg-white dark:bg-neutral-750 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white focus:border-[#1e4f4f] focus:ring-[#1e4f4f]/30"
                                    >
                                        <option v-for="category in categories" :key="category" :value="category">
                                            {{ category }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-2 text-gray-700 dark:text-neutral-300">
                                        Description
                                    </label>
                                    <textarea
                                        v-model="serviceForm.description"
                                        rows="4"
                                        placeholder="Enter service description"
                                        class="w-full px-4 py-3 rounded-xl transition-all duration-200 resize-none border focus:outline-none focus:ring-2 bg-white dark:bg-neutral-750 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-neutral-500 focus:border-[#1e4f4f] focus:ring-[#1e4f4f]/30"
                                    ></textarea>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-semibold mb-2 text-gray-700 dark:text-neutral-300">
                                            Price
                                        </label>
                                        <input
                                            v-model="serviceForm.price"
                                            type="text"
                                            placeholder="₱0"
                                            class="w-full px-4 py-3 rounded-xl transition-all duration-200 border focus:outline-none focus:ring-2 bg-white dark:bg-neutral-750 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-neutral-500 focus:border-[#1e4f4f] focus:ring-[#1e4f4f]/30"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold mb-2 text-gray-700 dark:text-neutral-300">
                                            Duration <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            v-model="serviceForm.duration"
                                            type="text"
                                            placeholder="e.g., 30 mins"
                                            @input="serviceForm.duration = validateDuration(serviceForm.duration)"
                                            class="w-full px-4 py-3 rounded-xl transition-all duration-200 border focus:outline-none focus:ring-2 bg-white dark:bg-neutral-750 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-neutral-500 focus:border-[#1e4f4f] focus:ring-[#1e4f4f]/30"
                                        />
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-2 text-gray-700 dark:text-neutral-300">
                                        Status
                                    </label>
                                    <select
                                        v-model="serviceForm.status"
                                        class="w-full px-4 py-3 rounded-xl transition-all duration-200 border focus:outline-none focus:ring-2 bg-white dark:bg-neutral-750 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white focus:border-[#1e4f4f] focus:ring-[#1e4f4f]/30"
                                    >
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="sticky bottom-0 px-6 py-4 border-t backdrop-blur-xl bg-white/95 dark:bg-neutral-800/95 border-gray-200 dark:border-neutral-700">
                                <div class="flex justify-end gap-3">
                                    <button
                                        @click="closeEditModal"
                                        class="px-6 py-2.5 rounded-xl font-semibold transition-all duration-200 bg-gray-100 dark:bg-neutral-700 hover:bg-gray-200 dark:hover:bg-neutral-600 text-gray-900 dark:text-white"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        @click="isEditing ? saveService() : addService()"
                                        class="px-6 py-2.5 rounded-xl font-semibold transition-all duration-200 bg-[#1e4f4f] hover:bg-[#2d5f5c] text-white"
                                    >
                                        {{ isEditing ? 'Save Changes' : 'Add Service' }}
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

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>