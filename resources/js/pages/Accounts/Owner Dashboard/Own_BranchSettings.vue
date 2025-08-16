<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import branchData from '@/tempData/branchData.json';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Branch Settings', href: '/clinic/branches' },
];

const searchQuery = ref('');
const selectedStatus = ref('All Status');
const currentPage = ref(1);
const itemsPerPage = ref(10);

// Modal states
const showViewModal = ref(false);
const showEditModal = ref(false);
const showAddModal = ref(false);
const selectedBranch = ref<any>(null);

// Form data for add/edit modal
const branchForm = ref({
    name: '',
    description: '',
    address: '',
    contactNumber: '',
    email: '',
    status: 'Active',
    establishedDate: ''
});

const filteredBranches = computed(() => {
    let filtered = branchData.branches;
    if (searchQuery.value) {
        filtered = filtered.filter(branch =>
            branch.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            branch.description.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            branch.address.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            branch.email.toLowerCase().includes(searchQuery.value.toLowerCase())
        );
    }
    if (selectedStatus.value !== 'All Status') {
        filtered = filtered.filter(branch => branch.status === selectedStatus.value);
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

const handleSearch = () => { currentPage.value = 1; };
const handleStatusFilter = (status: string) => { selectedStatus.value = status; currentPage.value = 1; };
const handlePageChange = (page: number) => { if (page >= 1 && page <= totalPages.value) currentPage.value = page; };

const handleViewBranch = (branchId: number) => {
    const branch = branchData.branches.find(b => b.id === branchId);
    selectedBranch.value = branch || null;
    showViewModal.value = true;
};

const handleAddBranch = () => {
    branchForm.value = {
        name: '',
        description: '',
        address: '',
        contactNumber: '',
        email: '',
        status: 'Active',
        establishedDate: ''
    };
    showAddModal.value = true;
};

const handleEditBranch = (branchId: number) => {
    const branch = branchData.branches.find(b => b.id === branchId);
    if (branch) {
        branchForm.value = { 
            name: branch.name,
            description: branch.description,
            address: branch.address,
            contactNumber: branch.contactNumber,
            email: branch.email,
            status: branch.status,
            establishedDate: branch.establishedDate
        };
    }
    showEditModal.value = true;
};

const closeViewModal = () => {
    showViewModal.value = false;
    selectedBranch.value = null;
};

const closeEditModal = () => {
    showEditModal.value = false;
    branchForm.value = {
        name: '',
        description: '',
        address: '',
        contactNumber: '',
        email: '',
        status: 'Active',
        establishedDate: ''
    };
};

const closeAddModal = () => {
    showAddModal.value = false;
    branchForm.value = {
        name: '',
        description: '',
        address: '',
        contactNumber: '',
        email: '',
        status: 'Active',
        establishedDate: ''
    };
};

const saveBranch = () => {
    // Handle save logic here
    console.log('Saving branch:', branchForm.value);
    closeEditModal();
};

const addBranch = () => {
    // Handle add logic here
    console.log('Adding branch:', branchForm.value);
    closeAddModal();
};
</script>

<template>
    <Head title="Branch Settings" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 rounded-xl p-4 bg-gray-50 min-h-screen relative">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-2 gap-4" :class="{ 'blur-[1px]': showViewModal || showEditModal || showAddModal }">
                <h1 class="text-3xl font-bold text-gray-900">Branch Settings</h1>
                <div class="flex flex-1 justify-end items-center gap-2">
                    <input
                        v-model="searchQuery"
                        @input="handleSearch"
                        type="text"
                        placeholder="Search by name, description, address..."
                        class="border border-gray-300 rounded px-4 py-2 w-80 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                    />
                </div>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-2 gap-4" :class="{ 'blur-[1px]': showViewModal || showEditModal || showAddModal }">
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
                        <option value="Maintenance">Maintenance</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button
                        @click="handleAddBranch"
                        class="bg-darkGreen-900 text-white px-6 py-2 rounded font-semibold shadow hover:bg-darkGreen-800 transition"
                    >Add Branch</button>
                    <button
                        @click="handleEditBranch(1)"
                        class="bg-darkGreen-900 text-white px-6 py-2 rounded font-semibold shadow hover:bg-darkGreen-800 transition"
                    >Edit</button>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow p-0 overflow-x-auto" :class="{ 'blur-[1px]': showViewModal || showEditModal || showAddModal }">
                <table class="w-full min-w-[1000px] border-separate border-spacing-0">
                    <thead>
                        <tr class="bg-darkGreen-900 text-white">
                            <th class="py-3 px-4 text-left font-semibold">ID</th>
                            <th class="py-3 px-4 text-left font-semibold">Branch Name</th>
                            <th class="py-3 px-4 text-left font-semibold">Address</th>
                            <th class="py-3 px-4 text-left font-semibold">Contact</th>
                            <th class="py-3 px-4 text-left font-semibold">Email</th>
                            <th class="py-3 px-4 text-left font-semibold">Status</th>
                            <th class="py-3 px-4 text-left font-semibold">Established</th>
                            <th class="py-3 px-4 text-left font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="paginatedBranches.length === 0">
                            <td colspan="8" class="text-center py-8 text-gray-500 text-lg">No branches found.</td>
                        </tr>
                        <tr v-for="branch in paginatedBranches" :key="branch.id" class="border-b last:border-b-0 hover:bg-gray-50">
                            <td class="py-3 px-4">{{ branch.id }}</td>
                            <td class="py-3 px-4 font-medium">{{ branch.name }}</td>
                            <td class="py-3 px-4">{{ branch.address }}</td>
                            <td class="py-3 px-4">{{ branch.contactNumber }}</td>
                            <td class="py-3 px-4">{{ branch.email }}</td>
                            <td class="py-3 px-4">
                                <span :class="{
                                    'px-2 py-1 rounded-full text-xs font-medium': true,
                                    'bg-green-100 text-green-800': branch.status === 'Active',
                                    'bg-red-100 text-red-800': branch.status === 'Inactive',
                                    'bg-yellow-100 text-yellow-800': branch.status === 'Maintenance'
                                }">
                                    {{ branch.status }}
                                </span>
                            </td>
                            <td class="py-3 px-4">{{ branch.establishedDate }}</td>
                            <td class="py-3 px-4">
                                <div class="flex gap-2">
                                    <button
                                        @click="handleViewBranch(branch.id)"
                                        class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition text-sm"
                                    >View</button>
                                    <button
                                        @click="handleEditBranch(branch.id)"
                                        class="bg-darkGreen-900 text-white px-3 py-1 rounded hover:bg-darkGreen-800 transition text-sm"
                                    >Edit</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mt-4 gap-4" :class="{ 'blur-[1px]': showViewModal || showEditModal || showAddModal }">
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

            <!-- View Branch Modal -->
            <div v-if="showViewModal" class="absolute inset-0 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">View Branch Details</h2>
                    <div v-if="selectedBranch" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">ID</label>
                                <input type="text" :value="selectedBranch.id" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Branch Name</label>
                                <input type="text" :value="selectedBranch.name" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                            </div>
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                <textarea :value="selectedBranch.description" readonly rows="3" class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50"></textarea>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                <input type="text" :value="selectedBranch.address" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                                <input type="text" :value="selectedBranch.contactNumber" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" :value="selectedBranch.email" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <input type="text" :value="selectedBranch.status" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Established Date</label>
                                <input type="text" :value="selectedBranch.establishedDate" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                            </div>
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Google Maps</label>
                            <a :href="selectedBranch.map" target="_blank" class="text-blue-600 hover:text-blue-800 underline">View in Google Maps</a>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button @click="closeViewModal" class="px-4 py-2 text-gray-700 bg-gray-200 rounded hover:bg-gray-300 transition">Close</button>
                    </div>
                </div>
            </div>

            <!-- Edit Branch Modal -->
            <div v-if="showEditModal" class="absolute inset-0 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Edit Branch</h2>
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Branch Name</label>
                                <input v-model="branchForm.name" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select v-model="branchForm.status" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="Maintenance">Maintenance</option>
                                </select>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                <textarea v-model="branchForm.description" rows="3" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"></textarea>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                <input v-model="branchForm.address" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
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
                                <label class="block text-sm font-medium text-gray-700 mb-1">Established Date</label>
                                <input v-model="branchForm.establishedDate" type="date" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
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
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Branch Name</label>
                                <input v-model="branchForm.name" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select v-model="branchForm.status" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="Maintenance">Maintenance</option>
                                </select>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                <textarea v-model="branchForm.description" rows="3" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"></textarea>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                <input v-model="branchForm.address" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
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
                                <label class="block text-sm font-medium text-gray-700 mb-1">Established Date</label>
                                <input v-model="branchForm.establishedDate" type="date" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
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