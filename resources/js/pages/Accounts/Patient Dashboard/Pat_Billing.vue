<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import billingData from '@/tempData/billingData.json';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'My Billing', href: '/billing' },
];

const searchQuery = ref('');
const selectedStatus = ref('All Status');
const currentPage = ref(1);
const itemsPerPage = ref(10);

// Modal states
const showViewModal = ref(false);
const selectedBilling = ref<any>(null);

// Filter billing records for current patient (in real app, this would be filtered by patient ID)
const patientBillingRecords = computed(() => {
    // For demo purposes, showing all records. In real app, filter by current patient ID
    return billingData.billingRecords;
});

const filteredBillingRecords = computed(() => {
    let filtered = patientBillingRecords.value;
    if (searchQuery.value) {
        filtered = filtered.filter(billing =>
            billing.patientName.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            billing.patientId.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            billing.dentistName.toLowerCase().includes(searchQuery.value.toLowerCase())
        );
    }
    if (selectedStatus.value !== 'All Status') {
        filtered = filtered.filter(billing => billing.status === selectedStatus.value);
    }
    return filtered;
});

const paginatedBillingRecords = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredBillingRecords.value.slice(start, end);
});

const totalPages = computed(() => {
    return Math.max(1, Math.ceil(filteredBillingRecords.value.length / itemsPerPage.value));
});

const handleSearch = () => { currentPage.value = 1; };
const handleStatusFilter = (status: string) => { selectedStatus.value = status; currentPage.value = 1; };
const handlePageChange = (page: number) => { if (page >= 1 && page <= totalPages.value) currentPage.value = page; };

const handleViewBilling = (billingId: string) => {
    const billing = billingData.billingRecords.find(b => b.id === billingId);
    selectedBilling.value = billing || null;
    showViewModal.value = true;
};

const closeViewModal = () => {
    showViewModal.value = false;
    selectedBilling.value = null;
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'Paid': return 'bg-green-100 text-green-800';
        case 'Pending': return 'bg-yellow-100 text-yellow-800';
        case 'Overdue': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};
</script>

<template>
    <Head title="My Billing" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">My Billing</h1>
            </div>

            <!-- Search and Filter Section -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex flex-col sm:flex-row gap-4 mb-6">
                    <div class="flex-1">
                        <input
                            v-model="searchQuery"
                            @input="handleSearch"
                            type="text"
                            placeholder="Search by patient name, ID, or dentist..."
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                        />
                    </div>
                    <div class="flex gap-2">
                        <select
                            v-model="selectedStatus"
                            @change="handleStatusFilter"
                            class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                        >
                            <option value="All Status">All Status</option>
                            <option value="Paid">Paid</option>
                            <option value="Pending">Pending</option>
                            <option value="Overdue">Overdue</option>
                        </select>
                    </div>
                </div>

                <!-- Billing Records Table -->
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse border border-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="border border-gray-200 py-3 px-4 text-left font-semibold text-gray-700">Billing ID</th>
                                <th class="border border-gray-200 py-3 px-4 text-left font-semibold text-gray-700">Patient Info</th>
                                <th class="border border-gray-200 py-3 px-4 text-left font-semibold text-gray-700">Dentist</th>
                                <th class="border border-gray-200 py-3 px-4 text-left font-semibold text-gray-700">Branch</th>
                                <th class="border border-gray-200 py-3 px-4 text-left font-semibold text-gray-700">Billing Date</th>
                                <th class="border border-gray-200 py-3 px-4 text-left font-semibold text-gray-700">Due Date</th>
                                <th class="border border-gray-200 py-3 px-4 text-left font-semibold text-gray-700">Total Amount</th>
                                <th class="border border-gray-200 py-3 px-4 text-left font-semibold text-gray-700">Status</th>
                                <th class="border border-gray-200 py-3 px-4 text-left font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="paginatedBillingRecords.length === 0">
                                <td colspan="9" class="text-center py-8 text-gray-500 text-lg">No billing records found.</td>
                            </tr>
                            <tr v-for="billing in paginatedBillingRecords" :key="billing.id" class="border-b last:border-b-0 hover:bg-gray-50">
                                <td class="py-3 px-4 font-medium">{{ billing.id }}</td>
                                <td class="py-3 px-4">
                                    <div>
                                        <div class="font-medium">{{ billing.patientName }}</div>
                                        <div class="text-sm text-gray-500">{{ billing.patientId }}</div>
                                    </div>
                                </td>
                                <td class="py-3 px-4">{{ billing.dentistName }}</td>
                                <td class="py-3 px-4">{{ billing.branchName }}</td>
                                <td class="py-3 px-4">{{ billing.billingDate }}</td>
                                <td class="py-3 px-4">{{ billing.dueDate }}</td>
                                <td class="py-3 px-4 font-semibold text-green-600">₱{{ billing.total.toLocaleString() }}</td>
                                <td class="py-3 px-4">
                                    <span :class="`px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(billing.status)}`">
                                        {{ billing.status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <button
                                        @click="handleViewBilling(billing.id)"
                                        class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition text-sm"
                                    >View Details</button>
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

            <!-- View Billing Modal -->
            <div v-if="showViewModal" class="absolute inset-0 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-4xl mx-4 max-h-[90vh] overflow-y-auto">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-900">Billing Details</h2>
                        <button
                            @click="closeViewModal"
                            class="text-gray-400 hover:text-gray-600 text-2xl font-bold"
                        >&times;</button>
                    </div>
                    <div v-if="selectedBilling" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Billing ID</label>
                                <input type="text" :value="selectedBilling.id" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <input type="text" :value="selectedBilling.status" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Patient Name</label>
                                <input type="text" :value="selectedBilling.patientName" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Patient ID</label>
                                <input type="text" :value="selectedBilling.patientId" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Dentist</label>
                                <input type="text" :value="selectedBilling.dentistName" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                                <input type="text" :value="selectedBilling.branchName" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Billing Date</label>
                                <input type="text" :value="selectedBilling.billingDate" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                                <input type="text" :value="selectedBilling.dueDate" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                            </div>
                        </div>

                        <!-- Procedures -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Procedures</label>
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <table class="w-full">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Procedure</th>
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Description</th>
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Unit Price</th>
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="procedure in selectedBilling.procedures" :key="procedure.procedure" class="border-t border-gray-200">
                                            <td class="px-4 py-2">{{ procedure.procedure }}</td>
                                            <td class="px-4 py-2">{{ procedure.description }}</td>
                                            <td class="px-4 py-2">₱{{ procedure.unitPrice.toLocaleString() }}</td>
                                            <td class="px-4 py-2 font-medium">₱{{ procedure.total.toLocaleString() }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Total Amount -->
                        <div class="border-t border-gray-200 pt-4">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-semibold text-gray-900">Total Amount:</span>
                                <span class="text-2xl font-bold text-green-600">₱{{ selectedBilling.total.toLocaleString() }}</span>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div v-if="selectedBilling.notes">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                            <textarea readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" rows="3">{{ selectedBilling.notes }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
