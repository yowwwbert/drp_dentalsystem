<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import billingData from '@/tempData/billingData.json';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Billing Management', href: '/billing' },
];

const searchQuery = ref('');
const selectedStatus = ref('All Status');
const selectedBranch = ref('All Branches');
const currentPage = ref(1);
const itemsPerPage = ref(10);

// Modal states
const showViewModal = ref(false);
const showEditBillingModal = ref(false);
const showPaymentModal = ref(false);
const selectedBilling = ref<any>(null);

// Form data for edit billing modal
const billingForm = ref({
    appointmentId: '',
    patientName: '',
    patientId: '',
    dentistName: '',
    branchName: '',
    billingDate: '',
    dueDate: '',
    status: '',
    paymentMethod: '',
    paymentDate: '',
    procedures: [],
    notes: ''
});

// Payment form data
const paymentForm = ref({
    paymentMethod: '',
    paymentDate: '',
    amount: 0,
    referenceNumber: '',
    notes: ''
});

const paymentMethods = [
    'Cash',
    'Credit Card',
    'Debit Card',
    'Bank Transfer',
    'GCash',
    'PayMaya',
    'Online Banking'
];

const filteredBillingRecords = computed(() => {
    let filtered = billingData.billingRecords;
    if (searchQuery.value) {
        filtered = filtered.filter(billing =>
            billing.patientName.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            billing.patientId.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            billing.dentistName.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            billing.branchName.toLowerCase().includes(searchQuery.value.toLowerCase())
        );
    }
    if (selectedStatus.value !== 'All Status') {
        filtered = filtered.filter(billing => billing.status === selectedStatus.value);
    }
    if (selectedBranch.value !== 'All Branches') {
        filtered = filtered.filter(billing => billing.branchName === selectedBranch.value);
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
const handleBranchFilter = (branch: string) => { selectedBranch.value = branch; currentPage.value = 1; };
const handlePageChange = (page: number) => { if (page >= 1 && page <= totalPages.value) currentPage.value = page; };

const handleViewBilling = (billingId: string) => {
    const billing = billingData.billingRecords.find(b => b.id === billingId);
    selectedBilling.value = billing || null;
    showViewModal.value = true;
};

const handleEditBilling = (billingId: string) => {
    const billing = billingData.billingRecords.find(b => b.id === billingId);
    if (billing) {
        billingForm.value = {
            appointmentId: billing.appointmentId.toString(),
            patientName: billing.patientName,
            patientId: billing.patientId,
            dentistName: billing.dentistName,
            branchName: billing.branchName,
            billingDate: billing.billingDate,
            dueDate: billing.dueDate,
            status: billing.status,
            paymentMethod: billing.paymentMethod,
            paymentDate: billing.paymentDate,
            procedures: billing.procedures.map(p => ({ ...p })),
            notes: billing.notes
        };
    }
    showEditBillingModal.value = true;
};

const handlePaymentUpdate = (billingId: string) => {
    const billing = billingData.billingRecords.find(b => b.id === billingId);
    if (billing) {
        paymentForm.value = {
            paymentMethod: billing.paymentMethod || '',
            paymentDate: billing.paymentDate || new Date().toISOString().split('T')[0],
            amount: billing.total,
            referenceNumber: '',
            notes: ''
        };
        selectedBilling.value = billing;
        showPaymentModal.value = true;
    }
};

const closeViewModal = () => {
    showViewModal.value = false;
    selectedBilling.value = null;
};

const closeEditBillingModal = () => {
    showEditBillingModal.value = false;
};

const closePaymentModal = () => {
    showPaymentModal.value = false;
    selectedBilling.value = null;
};

const saveBillingChanges = () => {
    // In real app, this would save to API
    if (selectedBilling.value) {
        const billing = billingData.billingRecords.find(b => b.id === selectedBilling.value.id);
        if (billing) {
            billing.status = billingForm.value.status;
            billing.paymentMethod = billingForm.value.paymentMethod;
            billing.paymentDate = billingForm.value.paymentDate;
            billing.notes = billingForm.value.notes;
        }
    }
    closeEditBillingModal();
};

const processPayment = () => {
    // In real app, this would process payment and update billing status
    if (selectedBilling.value) {
        const billing = billingData.billingRecords.find(b => b.id === selectedBilling.value.id);
        if (billing) {
            billing.status = 'Paid';
            billing.paymentMethod = paymentForm.value.paymentMethod;
            billing.paymentDate = paymentForm.value.paymentDate;
        }
    }
    closePaymentModal();
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'Paid': return 'bg-green-100 text-green-800';
        case 'Pending': return 'bg-yellow-100 text-yellow-800';
        case 'Overdue': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const formatDate = (dateString: string) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
</script>

<template>
    <Head title="Billing Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">Billing Management</h1>
                <button class="bg-darkGreen-900 text-white px-4 py-2 rounded-lg hover:bg-darkGreen-800 transition-colors duration-200">
                    Create New Billing
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
                            placeholder="Search by patient name, ID, dentist, or branch..."
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
                        <select
                            v-model="selectedBranch"
                            @change="handleBranchFilter"
                            class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                        >
                            <option value="All Branches">All Branches</option>
                            <option value="Main Branch">Main Branch</option>
                            <option value="North Branch">North Branch</option>
                            <option value="South Branch">South Branch</option>
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
                                <td class="py-3 px-4">{{ formatDate(billing.billingDate) }}</td>
                                <td class="py-3 px-4">{{ formatDate(billing.dueDate) }}</td>
                                <td class="py-3 px-4 font-semibold text-green-600">₱{{ billing.total.toLocaleString() }}</td>
                                <td class="py-3 px-4">
                                    <span :class="`px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(billing.status)}`">
                                        {{ billing.status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex gap-2">
                                        <button
                                            @click="handleViewBilling(billing.id)"
                                            class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition text-sm"
                                        >View</button>
                                        <button
                                            @click="handleEditBilling(billing.id)"
                                            class="bg-darkGreen-900 text-white px-3 py-1 rounded hover:bg-darkGreen-800 transition text-sm"
                                        >Edit</button>
                                        <button
                                            v-if="billing.status !== 'Paid'"
                                            @click="handlePaymentUpdate(billing.id)"
                                            class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 transition text-sm"
                                        >Payment</button>
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

            <!-- View Billing Modal -->
            <div v-if="showViewModal" class="absolute inset-0 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-4xl mx-4 max-h-[90vh] overflow-y-auto">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-900">View Billing Details</h2>
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
                                <input type="text" :value="formatDate(selectedBilling.billingDate)" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                                <input type="text" :value="formatDate(selectedBilling.dueDate)" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
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

            <!-- Edit Billing Modal -->
            <div v-if="showEditBillingModal" class="absolute inset-0 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-4xl mx-4 max-h-[90vh] overflow-y-auto">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-900">Edit Billing</h2>
                        <button
                            @click="closeEditBillingModal"
                            class="text-gray-400 hover:text-gray-600 text-2xl font-bold"
                        >&times;</button>
                    </div>
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select
                                    v-model="billingForm.status"
                                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                                >
                                    <option value="Pending">Pending</option>
                                    <option value="Paid">Paid</option>
                                    <option value="Overdue">Overdue</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                                <select
                                    v-model="billingForm.paymentMethod"
                                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                                >
                                    <option value="">Select Payment Method</option>
                                    <option v-for="method in paymentMethods" :key="method" :value="method">
                                        {{ method }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Date</label>
                                <input
                                    v-model="billingForm.paymentDate"
                                    type="date"
                                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                                <input
                                    v-model="billingForm.dueDate"
                                    type="date"
                                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                            <textarea
                                v-model="billingForm.notes"
                                rows="3"
                                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                            ></textarea>
                        </div>

                        <div class="flex gap-2 pt-4">
                            <button
                                @click="saveBillingChanges"
                                class="flex-1 bg-darkGreen-900 text-white px-4 py-2 rounded hover:bg-darkGreen-800 transition-colors duration-200"
                            >Save Changes</button>
                            <button
                                @click="closeEditBillingModal"
                                class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 transition-colors duration-200"
                            >Cancel</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Update Modal -->
            <div v-if="showPaymentModal" class="absolute inset-0 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-900">Update Payment</h2>
                        <button
                            @click="closePaymentModal"
                            class="text-gray-400 hover:text-gray-600 text-2xl font-bold"
                        >&times;</button>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                            <select
                                v-model="paymentForm.paymentMethod"
                                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                            >
                                <option value="">Select Payment Method</option>
                                <option v-for="method in paymentMethods" :key="method" :value="method">
                                    {{ method }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Payment Date</label>
                            <input
                                v-model="paymentForm.paymentDate"
                                type="date"
                                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                            <input
                                v-model="paymentForm.amount"
                                type="number"
                                step="0.01"
                                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reference Number</label>
                            <input
                                v-model="paymentForm.referenceNumber"
                                type="text"
                                placeholder="Optional"
                                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                            <textarea
                                v-model="paymentForm.notes"
                                rows="3"
                                placeholder="Optional payment notes..."
                                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                            ></textarea>
                        </div>

                        <div class="flex gap-2 pt-4">
                            <button
                                @click="processPayment"
                                class="flex-1 bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition-colors duration-200"
                            >Process Payment</button>
                            <button
                                @click="closePaymentModal"
                                class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 transition-colors duration-200"
                            >Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template> 