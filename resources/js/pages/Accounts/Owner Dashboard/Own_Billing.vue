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
const showCreateBillingModal = ref(false);
const showEditBillingModal = ref(false);
const selectedBilling = ref<any>(null);

// Form data for create billing modal
const billingForm = ref({
    appointmentId: '',
    patientName: '',
    patientId: '',
    dentistName: '',
    branchName: '',
    billingDate: new Date().toISOString().split('T')[0],
    dueDate: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    status: 'Pending',
    paymentMethod: '',
    paymentDate: '',
    procedures: [
        {
            procedure: '',
            description: '',
            unitPrice: 0,
            total: 0
        }
    ],
    notes: ''
});

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

const handleCreateBilling = () => {
    billingForm.value = {
        appointmentId: '',
        patientName: '',
        patientId: '',
        dentistName: '',
        branchName: '',
        billingDate: new Date().toISOString().split('T')[0],
        dueDate: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
        status: 'Pending',
        paymentMethod: '',
        paymentDate: '',
        procedures: [],
        notes: ''
    };
    showCreateBillingModal.value = true;
};

const handleAppointmentSelection = () => {
    if (billingForm.value.appointmentId) {
        const appointment = billingData.appointments.find(a => a.id === parseInt(billingForm.value.appointmentId));
        if (appointment) {
            billingForm.value.patientName = appointment.patientName;
            billingForm.value.patientId = appointment.patientId;
            billingForm.value.dentistName = appointment.dentistName;
            billingForm.value.branchName = appointment.branchName;
            
            // Auto-populate procedure based on appointment service type
            billingForm.value.procedures = [
                {
                    procedure: appointment.serviceType,
                    description: `Dental ${appointment.serviceType.toLowerCase()} procedure`,
                    unitPrice: 0,
                    total: 0
                }
            ];
        }
    }
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

const closeViewModal = () => {
    showViewModal.value = false;
    selectedBilling.value = null;
};

const closeCreateBillingModal = () => {
    showCreateBillingModal.value = false;
    billingForm.value = {
        appointmentId: '',
        patientName: '',
        patientId: '',
        dentistName: '',
        branchName: '',
        billingDate: new Date().toISOString().split('T')[0],
        dueDate: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
        status: 'Pending',
        paymentMethod: '',
        paymentDate: '',
        procedures: [],
        notes: ''
    };
};

const closeEditBillingModal = () => {
    showEditBillingModal.value = false;
};

const addProcedure = () => {
    billingForm.value.procedures.push({
        procedure: '',
        description: '',
        unitPrice: 0,
        total: 0
    });
};

const removeProcedure = (index: number) => {
    if (billingForm.value.procedures.length > 1) {
        billingForm.value.procedures.splice(index, 1);
    }
};

const updateProcedureTotal = (index: number) => {
    const procedure = billingForm.value.procedures[index];
    procedure.total = procedure.unitPrice;
};

const getTotal = computed(() => {
    return billingForm.value.procedures.reduce((sum, procedure) => sum + procedure.total, 0);
});

const saveBilling = () => {
    console.log('Saving billing:', billingForm.value);
    closeEditBillingModal();
};

const createBilling = () => {
    console.log('Creating billing:', billingForm.value);
    closeCreateBillingModal();
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'Paid':
            return 'bg-green-100 text-green-800';
        case 'Partially Paid':
            return 'bg-yellow-100 text-yellow-800';
        case 'Pending':
            return 'bg-blue-100 text-blue-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const getBranches = computed(() => {
    const branches = [...new Set(billingData.billingRecords.map(b => b.branchName))];
    return branches;
});
</script>

<template>
    <Head title="Billing Management" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 rounded-xl p-4 bg-gray-50 min-h-screen relative">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-2 gap-4" :class="{ 'blur-[1px]': showViewModal || showCreateBillingModal || showEditBillingModal }">
                <h1 class="text-3xl font-bold text-gray-900">Billing Management</h1>
                <div class="flex flex-1 justify-end items-center gap-2">
                    <input
                        v-model="searchQuery"
                        @input="handleSearch"
                        type="text"
                        placeholder="Search by patient name, ID, dentist, branch..."
                        class="border border-gray-300 rounded px-4 py-2 w-80 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                    />
                </div>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-2 gap-4" :class="{ 'blur-[1px]': showViewModal || showCreateBillingModal || showEditBillingModal }">
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <label class="font-medium text-gray-700 mr-2">Status</label>
                        <select
                            v-model="selectedStatus"
                            @change="handleStatusFilter(selectedStatus)"
                            class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                        >
                            <option>All Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Partially Paid">Partially Paid</option>
                            <option value="Paid">Paid</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="font-medium text-gray-700 mr-2">Branch</label>
                        <select
                            v-model="selectedBranch"
                            @change="handleBranchFilter(selectedBranch)"
                            class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                        >
                            <option>All Branches</option>
                            <option v-for="branch in getBranches" :key="branch" :value="branch">{{ branch }}</option>
                        </select>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button
                        @click="handleCreateBilling"
                        class="bg-darkGreen-900 text-white px-6 py-2 rounded font-semibold shadow hover:bg-darkGreen-800 transition"
                    >Create Billing</button>
                    <button
                        @click="handleEditBilling('B001')"
                        class="bg-darkGreen-900 text-white px-6 py-2 rounded font-semibold shadow hover:bg-darkGreen-800 transition"
                    >Edit</button>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow p-0 overflow-x-auto" :class="{ 'blur-[1px]': showViewModal || showCreateBillingModal || showEditBillingModal }">
                <table class="w-full min-w-[1200px] border-separate border-spacing-0">
                    <thead>
                        <tr class="bg-darkGreen-900 text-white">
                            <th class="py-3 px-4 text-left font-semibold">Billing ID</th>
                            <th class="py-3 px-4 text-left font-semibold">Patient</th>
                            <th class="py-3 px-4 text-left font-semibold">Dentist</th>
                            <th class="py-3 px-4 text-left font-semibold">Branch</th>
                            <th class="py-3 px-4 text-left font-semibold">Billing Date</th>
                            <th class="py-3 px-4 text-left font-semibold">Due Date</th>
                            <th class="py-3 px-4 text-left font-semibold">Total Amount</th>
                            <th class="py-3 px-4 text-left font-semibold">Status</th>
                            <th class="py-3 px-4 text-left font-semibold">Actions</th>
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
                                <div class="flex gap-2">
                                    <button
                                        @click="handleViewBilling(billing.id)"
                                        class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition text-sm"
                                    >View</button>
                                    <button
                                        @click="handleEditBilling(billing.id)"
                                        class="bg-darkGreen-900 text-white px-3 py-1 rounded hover:bg-darkGreen-800 transition text-sm"
                                    >Edit</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mt-4 gap-4" :class="{ 'blur-[1px]': showViewModal || showCreateBillingModal || showEditBillingModal }">
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

            <!-- View Billing Modal -->
            <div v-if="showViewModal" class="absolute inset-0 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-4xl mx-4 max-h-[90vh] overflow-y-auto">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">View Billing Details</h2>
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
                        
                        <div class="mt-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">Procedures</h3>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div v-for="(procedure, index) in selectedBilling.procedures" :key="index" class="mb-3 p-3 bg-white rounded border">
                                    <div class="grid grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Procedure</label>
                                            <input type="text" :value="procedure.procedure" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                            <input type="text" :value="procedure.description" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Unit Price</label>
                                            <input type="text" :value="`₱${procedure.unitPrice.toLocaleString()}`" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                                        </div>
                                    </div>
                                    <div class="mt-2 text-right">
                                        <span class="font-semibold text-lg">Total: ₱{{ procedure.total.toLocaleString() }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="text-right">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Total Amount</label>
                                <input type="text" :value="`₱${selectedBilling.total.toLocaleString()}`" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50 font-semibold text-lg text-center" />
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                            <textarea :value="selectedBilling.notes" readonly rows="3" class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50"></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button @click="closeViewModal" class="px-4 py-2 text-gray-700 bg-gray-200 rounded hover:bg-gray-300 transition">Close</button>
                    </div>
                </div>
            </div>

            <!-- Create Billing Modal -->
            <div v-if="showCreateBillingModal" class="absolute inset-0 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-4xl mx-4 max-h-[90vh] overflow-y-auto">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Create New Billing</h2>
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Select Appointment</label>
                                <select v-model="billingForm.appointmentId" @change="handleAppointmentSelection" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900">
                                    <option value="">Select an appointment</option>
                                    <option v-for="appointment in billingData.appointments" :key="appointment.id" :value="appointment.id">
                                        {{ appointment.patientName }} - {{ appointment.serviceType }} ({{ appointment.appointmentDate }})
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <input type="text" value="Pending" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Billing Date</label>
                                <input v-model="billingForm.billingDate" type="date" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                                <input v-model="billingForm.dueDate" type="date" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Patient Name</label>
                                <input v-model="billingForm.patientName" type="text" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Patient ID</label>
                                <input v-model="billingForm.patientId" type="text" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Dentist</label>
                                <input v-model="billingForm.dentistName" type="text" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                                <input v-model="billingForm.branchName" type="text" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                            </div>
                        </div>
                        
                        <div class="mt-6">
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="text-lg font-semibold text-gray-900">Procedures</h3>
                                <button @click="addProcedure" class="bg-darkGreen-900 text-white px-3 py-1 rounded text-sm hover:bg-darkGreen-800 transition">Add Procedure</button>
                            </div>
                            <div class="space-y-3">
                                <div v-for="(procedure, index) in billingForm.procedures" :key="index" class="p-4 bg-gray-50 rounded-lg border">
                                    <div class="grid grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Procedure</label>
                                            <input v-model="procedure.procedure" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                            <input v-model="procedure.description" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Unit Price</label>
                                            <input v-model="procedure.unitPrice" @input="updateProcedureTotal(index)" type="number" min="0" step="0.01" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-center mt-2">
                                        <span class="font-semibold">Total: ₱{{ procedure.total.toLocaleString() }}</span>
                                        <button v-if="billingForm.procedures.length > 1" @click="removeProcedure(index)" class="text-red-600 hover:text-red-800 text-sm">Remove</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="text-right">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Total Amount</label>
                                <input type="text" :value="`₱${getTotal.toLocaleString()}`" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50 font-semibold text-lg text-center" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                            <textarea v-model="billingForm.notes" rows="3" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button @click="closeCreateBillingModal" class="px-4 py-2 text-gray-700 bg-gray-200 rounded hover:bg-gray-300 transition">Cancel</button>
                        <button @click="createBilling" class="px-4 py-2 text-white bg-darkGreen-900 rounded hover:bg-darkGreen-800 transition">Create Billing</button>
                    </div>
                </div>
            </div>

            <!-- Edit Billing Modal -->
            <div v-if="showEditBillingModal" class="absolute inset-0 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-4xl mx-4 max-h-[90vh] overflow-y-auto">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Edit Billing</h2>
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select v-model="billingForm.status" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900">
                                    <option value="Pending">Pending</option>
                                    <option value="Partially Paid">Partially Paid</option>
                                    <option value="Paid">Paid</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                                <select v-model="billingForm.paymentMethod" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900">
                                    <option value="">Select payment method</option>
                                    <option v-for="method in billingData.paymentMethods" :key="method" :value="method">{{ method }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Date</label>
                                <input v-model="billingForm.paymentDate" type="date" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                                <input v-model="billingForm.dueDate" type="date" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                            </div>
                        </div>

                        <div class="mt-6">
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="text-lg font-semibold text-gray-900">Procedures</h3>
                                <button @click="addProcedure" class="bg-darkGreen-900 text-white px-3 py-1 rounded text-sm hover:bg-darkGreen-800 transition">Add Procedure</button>
                            </div>
                            <div class="space-y-3">
                                <div v-for="(procedure, index) in billingForm.procedures" :key="index" class="p-4 bg-gray-50 rounded-lg border">
                                    <div class="grid grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Procedure</label>
                                            <input v-model="procedure.procedure" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                            <input v-model="procedure.description" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Unit Price</label>
                                            <input v-model="procedure.unitPrice" @input="updateProcedureTotal(index)" type="number" min="0" step="0.01" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-center mt-2">
                                        <span class="font-semibold">Total: ₱{{ procedure.total.toLocaleString() }}</span>
                                        <button v-if="billingForm.procedures.length > 1" @click="removeProcedure(index)" class="text-red-600 hover:text-red-800 text-sm">Remove</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="text-right">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Total Amount</label>
                                <input type="text" :value="`₱${getTotal.toLocaleString()}`" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50 font-semibold text-lg text-center" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                            <textarea v-model="billingForm.notes" rows="3" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button @click="closeEditBillingModal" class="px-4 py-2 text-gray-700 bg-gray-200 rounded hover:bg-gray-300 transition">Cancel</button>
                        <button @click="saveBilling" class="px-4 py-2 text-white bg-darkGreen-900 rounded hover:bg-darkGreen-800 transition">Save Changes</button>
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
.hover\:bg-darkGreen-800:hover {
  background-color: #1a4545;
}
.focus\:ring-darkGreen-900:focus {
  --tw-ring-color: #1e4f4f;
}
</style> 