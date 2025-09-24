```vue
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import axios from 'axios';

const page = usePage();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Services List', href: '/clinic/services' },
];

const searchQuery = ref('');
const selectedStatus = ref('All Status');
const selectedCategory = ref('All Categories');
const currentPage = ref(1);
const itemsPerPage = ref(10);

// Hardcoded categories (based on typical values for treatment_type)
const categories = ['General', 'Restorative', 'Cosmetic', 'Surgical', 'Preventive', 'Orthodontic', 'Pediatric', 'Endodontic', 'Periodontic'];

// Modal states
const showViewModal = ref(false);
const showEditModal = ref(false);
const isEditing = ref(false);
const selectedService = ref<any>(null);

// Form data for add/edit modal
const serviceForm = ref({
    title: '',
    category: '',
    description: '',
    price: '',
    duration: '',
    status: 'Active'
});

// State for treatments
const treatments = ref<any[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);

// Fetch treatments from API
const fetchTreatments = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get('/dashboard/clinic/api/treatments');
        treatments.value = response.data.map((treatment: any) => ({
            id: treatment.treatment_id,
            title: treatment.treatment_name,
            category: treatment.treatment_type || 'General',
            duration: treatment.treatment_duration,
            description: treatment.treatment_description || 'No description available',
            price: treatment.treatment_cost || '₱0',
            status: treatment.is_active ? 'Active' : 'Inactive',
            icon: treatment.icon || '/default-icon.png'
        }));
    } catch (err) {
        console.error('Error fetching treatments:', err);
        error.value = 'Failed to load treatments. Please try again.';
    } finally {
        loading.value = false;
    }
};

// Fetch treatments on component mount
fetchTreatments();

const filteredServices = computed(() => {
    let filtered = treatments.value;
    if (searchQuery.value) {
        filtered = filtered.filter(service =>
            service.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            service.category.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            service.description.toLowerCase().includes(searchQuery.value.toLowerCase())
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

const totalPages = computed(() => {
    return Math.max(1, Math.ceil(filteredServices.value.length / itemsPerPage.value));
});

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
        status: 'Active'
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
            status: service.status
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
        status: 'Active'
    };
};

const addService = async () => {
    if (!serviceForm.value.title || !serviceForm.value.duration) {
        error.value = 'Title and duration are required.';
        return;
    }
    try {
        await axios.post('/dashboard/clinic/api/treatments', {
            treatment_name: serviceForm.value.title,
            treatment_type: serviceForm.value.category,
            treatment_duration: serviceForm.value.duration,
            treatment_description: serviceForm.value.description,
            treatment_cost: serviceForm.value.price,
            is_active: serviceForm.value.status === 'Active'
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
    try {
        await axios.put(`/dashboard/clinic/api/treatments/${selectedService.value.id}`, {
            treatment_name: serviceForm.value.title,
            treatment_type: serviceForm.value.category,
            treatment_duration: serviceForm.value.duration,
            treatment_description: serviceForm.value.description,
            treatment_cost: serviceForm.value.price,
            is_active: serviceForm.value.status === 'Active'
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
        <div class="flex flex-col gap-4 rounded-xl p-4 bg-gray-50 min-h-screen relative">
            <!-- Loading and Error States -->
            <div v-if="loading" class="text-center py-8">
                <span>Loading treatments...</span>
            </div>
            <div v-else-if="error" class="text-center py-8 text-red-600">
                {{ error }}
            </div>
            <div v-else>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-2 gap-4" :class="{ 'blur-[1px]': showViewModal || showEditModal }">
                    <h1 class="text-3xl font-bold text-gray-900">Services List</h1>
                    <div class="flex flex-1 justify-end items-center gap-2">
                        <input
                            v-model="searchQuery"
                            @input="handleSearch"
                            type="text"
                            placeholder="Search by title, category, or description..."
                            class="border border-gray-300 rounded px-4 py-2 w-80 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                        />
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-2 gap-4" :class="{ 'blur-[1px]': showViewModal || showEditModal }">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2">
                            <label class="font-medium text-gray-700 mr-2">Status</label>
                            <select
                                v-model="selectedStatus"
                                @change="handleStatusFilter(selectedStatus)"
                                class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                            >
                                <option>All Status</option>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="font-medium text-gray-700 mr-2">Category</label>
                            <select
                                v-model="selectedCategory"
                                @change="handleCategoryFilter(selectedCategory)"
                                class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                            >
                                <option>All Categories</option>
                                <option v-for="category in categories" :key="category" :value="category">{{ category }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button
                            @click="handleAddService"
                            class="bg-darkGreen-900 text-white px-6 py-2 rounded font-semibold shadow hover:bg-darkGreen-800 transition"
                        >Add Service</button>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow p-0 overflow-x-auto" :class="{ 'blur-[1px]': showViewModal || showEditModal }">
                    <table class="w-full min-w-[1000px] border-separate border-spacing-0">
                        <thead>
                            <tr class="bg-darkGreen-900 text-white">
                                <th class="py-3 px-4 text-left font-semibold">ID</th>
                                <th class="py-3 px-4 text-left font-semibold">Service</th>
                                <th class="py-3 px-4 text-left font-semibold">Category</th>
                                <th class="py-3 px-4 text-left font-semibold">Description</th>
                                <th class="py-3 px-4 text-left font-semibold">Price</th>
                                <th class="py-3 px-4 text-left font-semibold">Duration</th>
                                <th class="py-3 px-4 text-left font-semibold">Status</th>
                                <th class="py-3 px-4 text-left font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="paginatedServices.length === 0">
                                <td colspan="8" class="text-center py-8 text-gray-500 text-lg">No services found.</td>
                            </tr>
                            <tr v-for="service in paginatedServices" :key="service.id" class="border-b last:border-b-0 hover:bg-gray-50">
                                <td class="py-3 px-4">{{ service.id }}</td>
                                <td class="py-3 px-4">
                                    <span class="font-medium">{{ service.title }}</span>
                                </td>
                                <td class="py-3 px-4">{{ service.category }}</td>
                                <td class="py-3 px-4 max-w-xs truncate" :title="service.description">{{ service.description }}</td>
                                <td class="py-3 px-4 font-semibold text-green-600">{{ service.price }}</td>
                                <td class="py-3 px-4">{{ service.duration }}</td>
                                <td class="py-3 px-4">
                                    <span :class="{
                                        'px-2 py-1 rounded-full text-md font-medium': true,
                                        'bg-green-100 text-green-800': service.status === 'Active',
                                        'bg-red-100 text-red-800': service.status === 'Inactive'
                                    }">
                                        {{ service.status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex gap-2">
                                        <button
                                            @click="handleViewService(service.id)"
                                            class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition text-sm"
                                        >View</button>
                                        <button
                                            @click="handleEditService(service.id)"
                                            class="bg-darkGreen-900 text-white px-3 py-1 rounded hover:bg-darkGreen-800 transition text-sm"
                                        >Edit</button>
                                    </div>
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

                <!-- View Service Modal -->
                <div v-if="showViewModal" class="absolute inset-0 flex items-center justify-center z-50">
                    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">View Service Details</h2>
                        <div v-if="selectedService" class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">ID</label>
                                    <input type="text" :value="selectedService.id" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Service Title</label>
                                    <input type="text" :value="selectedService.title" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                                    <input type="text" :value="selectedService.category" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <textarea :value="selectedService.description" readonly rows="3" class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50"></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Price</label>
                                    <input type="text" :value="selectedService.price" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Duration</label>
                                    <input type="text" :value="selectedService.duration" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <input type="text" :value="selectedService.status" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Icon</label>
                                <img :src="selectedService.icon" :alt="selectedService.title + ' icon'" class="w-16 h-16 object-contain border border-gray-300 rounded p-2" />
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 mt-6">
                            <button @click="closeViewModal" class="px-4 py-2 text-gray-700 bg-gray-200 rounded hover:bg-gray-300 transition">Close</button>
                        </div>
                    </div>
                </div>

                <!-- Edit Service Modal -->
                <div v-if="showEditModal" class="absolute inset-0 flex items-center justify-center z-50">
                    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">{{ isEditing ? 'Edit Service' : 'Add New Service' }}</h2>
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Service Title</label>
                                    <input v-model="serviceForm.title" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                                    <select v-model="serviceForm.category" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900">
                                        <option v-for="category in categories" :key="category" :value="category">{{ category }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select v-model="serviceForm.status" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900">
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                    </select>
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <textarea v-model="serviceForm.description" rows="3" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Price</label>
                                    <input v-model="serviceForm.price" type="text" placeholder="₱0" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Duration</label>
                                    <input v-model="serviceForm.duration" type="text" placeholder="0 minutes" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 mt-6">
                            <button @click="closeEditModal" class="px-4 py-2 text-gray-700 bg-gray-200 rounded hover:bg-gray-300 transition">Cancel</button>
                            <button 
                                @click="isEditing ? saveService() : addService()" 
                                class="px-4 py-2 text-white bg-darkGreen-900 rounded hover:bg-darkGreen-800 transition"
                            >
                                {{ isEditing ? 'Save Changes' : 'Add Service' }}
                            </button>
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
```