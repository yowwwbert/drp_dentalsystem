<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';

// TypeScript interfaces
interface Procedure {
  name: string;
  description: string;
  unit_price: number;
  total: number;
  discount_type: string;
  discount_value: number;
  discount_amount: number;
  discount_reason?: string;
}

interface Billing {
  billing_id: string;
  patient_id: string;
  patient_name: string;
  billing_date: string;
  amount: number;
  status: string;
  procedures: Procedure[];
  discount_amount: Record<string, number>;
  discount_reason: Record<string, string>;
}

interface Appointment {
  appointment_id: string;
  patient_id: string;
  patient: string;
  date: string;
  time: string;
  status: string;
  services: string[];
  billing_id: string | null;
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Billing Management', href: '/billing' },
];

const searchQuery = ref('');
const selectedStatus = ref('All Status');
const currentPage = ref(1);
const itemsPerPage = ref(10);

// Modal states
const showViewModal = ref(false);
const showCreateBillingModal = ref(false);
const showEditBillingModal = ref(false);
const selectedBilling = ref<Billing | null>(null);

// Form data for create/edit billing modal
const billingForm = ref({
  billing_id: '',
  patient_id: '',
  patient_name: '',
  billing_date: new Date().toISOString().split('T')[0],
  amount: 0,
  status: 'Pending',
  procedures: [
    { name: '', description: '', unit_price: 0, total: 0, discount_type: '', discount_value: 0, discount_amount: 0 },
  ],
  discount_type: '',
  discount_value: 0,
  discount_scope: '',
  discount_reason: '',
});

// Appointment data
const appointments = ref<Appointment[]>([]);
const selectedAppointmentId = ref('');

// Discount visibility
const showDiscount = ref(false);

const billings = ref<Billing[]>([]);
const totalRecords = ref(0);

const fetchBillings = async () => {
  try {
    const response = await axios.get('/api/billings');
    billings.value = response.data.data;
    totalRecords.value = response.data.total_records;
  } catch (error) {
    console.error('Error fetching billings:', error);
    alert('Failed to fetch billings: ' + (error.response?.data?.error || 'Unknown error'));
  }
};

const fetchBillingById = async (billingId: string) => {
  try {
    const billing = billings.value.find((b: Billing) => b.billing_id === billingId);
    if (!billing) throw new Error('Billing not found');
    return billing;
  } catch (error) {
    console.error('Error fetching billing:', error);
    alert('Failed to fetch billing: ' + (error.message || 'Unknown error'));
    return null;
  }
};

const fetchAppointments = async () => {
  try {
    console.log("Fetching appointments...");

    const response = await axios.get('/dashboard/appointments');
    console.log("Raw response:", response);

    console.log("Appointments from response:", response.data.appointments);

    const validAppointments = response.data.appointments.filter((appointment: Appointment) => {
      const isValid = ['Scheduled', 'Checked In', 'Completed'].includes(appointment.status) &&
        appointment.billing_id === null;
      
      console.log(
        `Checking appointment ID ${appointment.appointment_id}:`,
        "Status:", appointment.status,
        "Billing ID:", appointment.billing_id,
        "Valid:", isValid
      );

      return isValid;
    });

    console.log("Filtered valid appointments:", validAppointments);

    appointments.value = validAppointments;
  } catch (error: any) {
    console.error('Error fetching appointments:', error);
    alert('Failed to fetch appointments: ' + (error.response?.data?.message || 'Unknown error'));
  }
};


onMounted(() => {
  fetchBillings();
  fetchAppointments();
});

watch(selectedAppointmentId, (newVal) => {
  if (newVal) {
    const appointment = appointments.value.find(a => a.appointment_id === newVal);
    if (appointment) {
      billingForm.value = {
        billing_id: '',
        patient_id: appointment.patient_id,
        patient_name: appointment.patient,
        billing_date: new Date().toISOString().split('T')[0],
        amount: 0,
        status: 'Pending',
        procedures: appointment.services.map(service => ({
          name: service,
          description: `Dental ${service.toLowerCase()} procedure`,
          unit_price: 0,
          total: 0,
          discount_type: '',
          discount_value: 0,
          discount_amount: 0,
        })),
        discount_type: '',
        discount_value: 0,
        discount_scope: '',
        discount_reason: '',
      };
    }
  } else {
    billingForm.value = {
      billing_id: '',
      patient_id: '',
      patient_name: '',
      billing_date: new Date().toISOString().split('T')[0],
      amount: 0,
      status: 'Pending',
      procedures: [{ name: '', description: '', unit_price: 0, total: 0, discount_type: '', discount_value: 0, discount_amount: 0 }],
      discount_type: '',
      discount_value: 0,
      discount_scope: '',
      discount_reason: '',
    };
  }
});

const filteredBillingRecords = computed(() => {
  let result = billings.value;
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    result = result.filter(billing =>
      billing.patient_name.toLowerCase().includes(query) ||
      billing.billing_id.toLowerCase().includes(query)
    );
  }
  if (selectedStatus.value !== 'All Status') {
    result = result.filter(billing => billing.status === selectedStatus.value);
  }
  return result;
});

const paginatedBillingRecords = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value;
  const end = start + itemsPerPage.value;
  return filteredBillingRecords.value.slice(start, end);
});

const totalPages = computed(() => {
  return Math.max(1, Math.ceil(filteredBillingRecords.value.length / itemsPerPage.value));
});

const handleSearch = () => {
  currentPage.value = 1;
};

const handleStatusFilter = (status: string) => {
  selectedStatus.value = status;
  currentPage.value = 1;
};

const handlePageChange = (page: number) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page;
  }
};

const handleViewBilling = async (billingId: string) => {
  const billing = await fetchBillingById(billingId);
  selectedBilling.value = billing || null;
  showViewModal.value = !!billing;
};

const handleCreateBilling = () => {
  billingForm.value = {
    billing_id: '',
    patient_id: '',
    patient_name: '',
    billing_date: new Date().toISOString().split('T')[0],
    amount: 0,
    status: 'Pending',
    procedures: [{ name: '', description: '', unit_price: 0, total: 0, discount_type: '', discount_value: 0, discount_amount: 0 }],
    discount_type: '',
    discount_value: 0,
    discount_scope: '',
    discount_reason: '',
  };
  selectedAppointmentId.value = '';
  showDiscount.value = false;
  showCreateBillingModal.value = true;
};

const handleEditBilling = async (billingId: string) => {
  const billing = await fetchBillingById(billingId);
  if (billing) {
    billingForm.value = {
      billing_id: billing.billing_id,
      patient_id: billing.patient_id,
      patient_name: billing.patient_name,
      billing_date: billing.billing_date,
      amount: billing.amount,
      status: billing.status,
      procedures: billing.procedures?.map(p => ({
        name: p.name,
        description: p.description || '',
        unit_price: p.unit_price,
        total: p.total,
        discount_type: p.discount_amount ? 'amount' : '',
        discount_value: p.discount_amount || 0,
        discount_amount: p.discount_amount || 0,
      })) || [{ name: '', description: '', unit_price: 0, total: 0, discount_type: '', discount_value: 0, discount_amount: 0 }],
      discount_type: billing.discount_amount?.whole ? 'amount' : '',
      discount_value: billing.discount_amount?.whole || 0,
      discount_scope: billing.discount_amount?.whole ? 'whole' : (billing.procedures.some((p: Procedure) => (p.discount_amount || 0) > 0) ? 'procedure' : ''),
      discount_reason: billing.discount_reason?.whole || '',
    };
    showDiscount.value = billingForm.value.discount_scope !== '';
    showEditBillingModal.value = true;
  }
};

const closeViewModal = () => {
  showViewModal.value = false;
  selectedBilling.value = null;
};

const closeCreateBillingModal = () => {
  showCreateBillingModal.value = false;
};

const closeEditBillingModal = () => {
  showEditBillingModal.value = false;
};

const addProcedure = () => {
  billingForm.value.procedures.push({ name: '', description: '', unit_price: 0, total: 0, discount_type: '', discount_value: 0, discount_amount: 0 });
};

const removeProcedure = (index: number) => {
  if (billingForm.value.procedures.length > 1) {
    billingForm.value.procedures.splice(index, 1);
  }
};

const updateProcedureTotal = (index: number) => {
  const procedure = billingForm.value.procedures[index];
  const baseTotal = procedure.unit_price || 0;
  procedure.discount_amount = 0;
  if (billingForm.value.discount_scope === 'procedure' && procedure.discount_type && procedure.discount_value > 0) {
    if (procedure.discount_type === 'percentage') {
      procedure.discount_amount = baseTotal * (procedure.discount_value / 100);
      procedure.total = baseTotal - procedure.discount_amount;
    } else if (procedure.discount_type === 'amount') {
      procedure.discount_amount = Math.min(procedure.discount_value, baseTotal);
      procedure.total = baseTotal - procedure.discount_amount;
    }
  } else {
    procedure.total = baseTotal;
  }
};

const subTotal = computed(() => {
  return billingForm.value.procedures.reduce((sum, procedure) => sum + (procedure.total || 0), 0);
});

const wholeDiscountAmount = computed(() => {
  if (billingForm.value.discount_scope === 'whole' && billingForm.value.discount_type && billingForm.value.discount_value > 0) {
    if (billingForm.value.discount_type === 'percentage') {
      return subTotal.value * (billingForm.value.discount_value / 100);
    } else if (procedure.discount_type === 'amount') {
      return Math.min(billingForm.value.discount_value, subTotal.value);
    }
  }
  return 0;
});

const getTotal = computed(() => {
  const total = subTotal.value - wholeDiscountAmount.value;
  billingForm.value.amount = total;
  return total;
});

const removeDiscount = () => {
  showDiscount.value = false;
  billingForm.value.discount_scope = '';
  billingForm.value.discount_type = '';
  billingForm.value.discount_value = 0;
  billingForm.value.discount_reason = '';
  billingForm.value.procedures.forEach((_, index) => {
    billingForm.value.procedures[index].discount_type = '';
    billingForm.value.procedures[index].discount_value = 0;
    billingForm.value.procedures[index].discount_amount = 0;
    updateProcedureTotal(index);
  });
};

const createBilling = async () => {
  if (!billingForm.value.patient_id || !selectedAppointmentId.value) {
    alert('Please select an appointment.');
    return;
  }
  try {
    const payload = {
      patient_id: billingForm.value.patient_id,
      billing_date: billingForm.value.billing_date,
      amount: getTotal.value,
      status: billingForm.value.status,
      procedures: billingForm.value.procedures.map(p => ({
        name: p.name,
        description: p.description,
        unit_price: p.unit_price,
        total: p.total,
        discount_amount: billingForm.value.discount_scope === 'procedure' ? p.discount_amount : 0,
        discount_reason: billingForm.value.discount_scope === 'procedure' ? p.discount_reason : '',
      })),
      discount_amount: billingForm.value.discount_scope === 'whole' && billingForm.value.discount_type ? { whole: wholeDiscountAmount.value } : {},
      discount_reason: billingForm.value.discount_scope === 'whole' && billingForm.value.discount_reason ? { whole: billingForm.value.discount_reason } : {},
      appointment_ids: [selectedAppointmentId.value],
    };
    await axios.post('/api/billings', payload);
    await Promise.all([fetchBillings(), fetchAppointments()]);
    closeCreateBillingModal();
  } catch (error) {
    console.error('Error creating billing:', error);
    alert('Failed to create billing: ' + (error.response?.data?.error || 'Unknown error'));
  }
};

const saveBilling = async () => {
  try {
    await axios.get('/sanctum/csrf-cookie');
    const payload = {
      patient_id: billingForm.value.patient_id,
      billing_date: billingForm.value.billing_date,
      amount: getTotal.value,
      status: billingForm.value.status,
      procedures: billingForm.value.procedures.map(p => ({
        name: p.name,
        description: p.description,
        unit_price: p.unit_price,
        total: p.total,
        discount_amount: billingForm.value.discount_scope === 'procedure' ? p.discount_amount : 0,
        discount_reason: billingForm.value.discount_scope === 'procedure' ? p.discount_reason : '',
      })),
      discount_amount: billingForm.value.discount_scope === 'whole' && billingForm.value.discount_type ? { whole: wholeDiscountAmount.value } : {},
      discount_reason: billingForm.value.discount_scope === 'whole' && billingForm.value.discount_reason ? { whole: billingForm.value.discount_reason } : {},
    };
    await axios.put(`/api/billings/${billingForm.value.billing_id}`, payload);
    await fetchBillings();
    closeEditBillingModal();
  } catch (error) {
    console.error('Error updating billing:', error);
    alert('Failed to update billing: ' + (error.response?.data?.error || 'Unknown error'));
  }
};

const getStatusColor = (status: string) => {
  switch (status) {
    case 'Paid': return 'bg-green-100 text-green-800';
    case 'Partially Paid': return 'bg-yellow-100 text-yellow-800';
    case 'Pending': return 'bg-blue-100 text-blue-800';
    default: return 'bg-gray-100 text-gray-800';
  }
};

const formatDateTime = (dateStr: string, timeStr: string) => {
  const date = new Date(`${dateStr}T${timeStr}Z`);
  return date.toLocaleString('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    hour12: true,
    timeZone: 'America/Los_Angeles',
  }).replace(' ', ' at ') + ' PST';
};

const formatBillingDate = (dateStr: string) => {
  if (!dateStr) return 'N/A';
  const date = new Date(dateStr);
  return date.toLocaleString('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
    timeZone: 'America/Los_Angeles',
  });
};
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
            placeholder="Search by patient name or billing ID..."
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
              @change="handleStatusFilter($event.target.value)"
              class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
            >
              <option>All Status</option>
              <option value="Pending">Pending</option>
              <option value="Partially Paid">Partially Paid</option>
              <option value="Paid">Paid</option>
            </select>
          </div>
        </div>
        <div class="flex gap-2">
          <button
            @click="handleCreateBilling"
            class="bg-darkGreen-900 text-white px-6 py-2 rounded font-semibold shadow hover:bg-darkGreen-800 transition"
          >Create Billing</button>
        </div>
      </div>
      <div class="bg-white rounded-xl shadow p-0 overflow-x-auto" :class="{ 'blur-[1px]': showViewModal || showCreateBillingModal || showEditBillingModal }">
        <table class="w-full min-w-[800px] border-separate border-spacing-0">
          <thead>
            <tr class="bg-darkGreen-900 text-white">
              <th class="py-3 px-4 text-left font-semibold">Billing ID</th>
              <th class="py-3 px-4 text-left font-semibold">Patient</th>
              <th class="py-3 px-4 text-left font-semibold">Billing Date</th>
              <th class="py-3 px-4 text-left font-semibold">Total Amount</th>
              <th class="py-3 px-4 text-left font-semibold">Status</th>
              <th class="py-3 px-4 text-left font-semibold">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="paginatedBillingRecords.length === 0">
              <td colspan="6" class="text-center py-8 text-gray-500 text-lg">No billing records found.</td>
            </tr>
            <tr v-for="billing in paginatedBillingRecords" :key="billing.billing_id" class="border-b last:border-b-0 hover:bg-gray-50">
              <td class="py-3 px-4 font-medium">{{ billing.billing_id }}</td>
              <td class="py-3 px-4">{{ billing.patient_name }}</td>
              <td class="py-3 px-4">{{ formatBillingDate(billing.billing_date) }}</td>
              <td class="py-3 px-4 font-semibold text-green-600">₱{{ billing.amount.toLocaleString() }}</td>
              <td class="py-3 px-4">
                <span :class="`px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(billing.status)}`">
                  {{ billing.status }}
                </span>
              </td>
              <td class="py-3 px-4">
                <div class="flex gap-2">
                  <button
                    @click="handleViewBilling(billing.billing_id)"
                    class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition text-sm"
                  >View</button>
                  <button
                    @click="handleEditBilling(billing.billing_id)"
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
                <input type="text" :value="selectedBilling.billing_id" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <input type="text" :value="selectedBilling.status" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Patient Name</label>
                <input type="text" :value="selectedBilling.patient_name" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Billing Date</label>
                <input type="text" :value="formatBillingDate(selectedBilling.billing_date)" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
              </div>
            </div>
            <div class="mt-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-3">Procedures</h3>
              <div class="bg-gray-50 rounded-lg p-4">
                <div v-for="(procedure, index) in selectedBilling.procedures" :key="index" class="mb-3 p-3 bg-white rounded border">
                  <div class="grid grid-cols-3 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Procedure</label>
                      <input type="text" :value="procedure.name" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                      <input type="text" :value="procedure.description" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Unit Price</label>
                      <input type="text" :value="`₱${procedure.unit_price?.toLocaleString() || '0.00'}`" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                    </div>
                  </div>
                  <div v-if="procedure.discount_amount > 0" class="mt-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Discount</label>
                    <input type="text" :value="`${procedure.discount_amount}${procedure.discount_type === 'percentage' ? '%' : ' ₱'} (${procedure.discount_reason || 'No reason'})`" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
                  </div>
                  <div class="mt-2 text-right">
                    <span class="font-semibold text-lg">Total: ₱{{ procedure.total?.toLocaleString() || '0.00' }}</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="mt-4">
              <div class="text-right">
                <label class="block text-sm font-medium text-gray-700 mb-1">Total Amount</label>
                <input type="text" :value="`₱${selectedBilling.amount?.toLocaleString() || '0.00'}`" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50 font-semibold text-lg text-center" />
              </div>
            </div>
            <div class="mt-4" v-if="selectedBilling.discount_amount?.whole">
              <label class="block text-sm font-medium text-gray-700 mb-1">Whole Billing Discount</label>
              <input type="text" :value="`${selectedBilling.discount_amount.whole}${selectedBilling.discount_type === 'percentage' ? '%' : ' ₱'} (${selectedBilling.discount_reason.whole || 'No reason'})`" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
            </div>
            <div class="mt-4">
              <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
              <textarea :value="selectedBilling.discount_reason?.whole || ''" readonly rows="3" class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50"></textarea>
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
                <select
                  v-model="selectedAppointmentId"
                  @change="selectedAppointmentId = $event.target.value"
                  class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                >
                  <option value="">Select an appointment</option>
                  <option v-for="appointment in appointments" :key="appointment.appointment_id" :value="appointment.appointment_id">
                    {{ appointment.patient }} - {{ formatDateTime(appointment.date, appointment.time) }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Billing Date</label>
                <input v-model="billingForm.billing_date" type="date" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Patient Name</label>
                <input v-model="billingForm.patient_name" type="text" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
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
                      <input v-model="procedure.name" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                      <input v-model="procedure.description" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Unit Price</label>
                      <input v-model.number="procedure.unit_price" @input="updateProcedureTotal(index)" type="number" min="0" step="0.01" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                    </div>
                  </div>
                  <div v-if="billingForm.discount_scope === 'procedure'" class="mt-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Discount Type</label>
                    <select v-model="procedure.discount_type" @change="updateProcedureTotal(index)" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900">
                      <option value="">None</option>
                      <option value="percentage">Percentage</option>
                      <option value="amount">Exact Amount</option>
                    </select>
                    <input v-if="procedure.discount_type" v-model.number="procedure.discount_value" type="number" min="0" step="0.01" :placeholder="procedure.discount_type === 'percentage' ? '0-100%' : 'Amount in ₱'" class="w-full border border-gray-300 rounded px-3 py-2 mt-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" @input="updateProcedureTotal(index)" />
                  </div>
                  <div class="flex justify-between items-center mt-2">
                    <span class="font-semibold">Total: ₱{{ procedure.total.toLocaleString() }}</span>
                    <button v-if="billingForm.procedures.length > 1" @click="removeProcedure(index)" class="text-red-600 hover:text-red-800 text-sm">Remove</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="mt-4">
              <button v-if="!showDiscount" @click="showDiscount = true" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">Add Discount</button>
              <div v-else>
                <div class="flex justify-between items-center mb-2">
                  <div class="flex items-center gap-2">
                    <label class="block text-sm font-medium text-gray-700">Apply Discount to</label>
                    <div class="flex items-center gap-4">
                      <label><input v-model="billingForm.discount_scope" type="radio" value="whole" class="mr-1"> Whole Billing</label>
                      <label><input v-model="billingForm.discount_scope" type="radio" value="procedure" class="mr-1"> Specific Procedure</label>
                    </div>
                  </div>
                  <button @click="removeDiscount" class="text-red-600 hover:text-red-800 text-sm">Remove Discount</button>
                </div>
                <div v-if="billingForm.discount_scope === 'whole'">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Discount Type</label>
                  <select v-model="billingForm.discount_type" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900">
                    <option value="">None</option>
                    <option value="percentage">Percentage</option>
                    <option value="amount">Exact Amount</option>
                  </select>
                  <input v-if="billingForm.discount_type" v-model.number="billingForm.discount_value" type="number" min="0" step="0.01" :placeholder="billingForm.discount_type === 'percentage' ? '0-100%' : 'Amount in ₱'" class="w-full border border-gray-300 rounded px-3 py-2 mt-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                </div>
              </div>
            </div>
            <div class="text-right">
              <label class="block text-sm font-medium text-gray-700 mb-1">Total Amount</label>
              <input type="text" :value="`₱${getTotal.toLocaleString()}`" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50 font-semibold text-lg text-center" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
              <textarea v-model="billingForm.discount_reason" rows="3" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"></textarea>
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
                <label class="block text-sm font-medium text-gray-700 mb-1">Billing ID</label>
                <input v-model="billingForm.billing_id" type="text" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Billing Date</label>
                <input v-model="billingForm.billing_date" type="date" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Patient Name</label>
                <input v-model="billingForm.patient_name" type="text" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select v-model="billingForm.status" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900">
                  <option value="Pending">Pending</option>
                  <option value="Partially Paid">Partially Paid</option>
                  <option value="Paid">Paid</option>
                </select>
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
                      <input v-model="procedure.name" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                      <input v-model="procedure.description" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Unit Price</label>
                      <input v-model.number="procedure.unit_price" @input="updateProcedureTotal(index)" type="number" min="0" step="0.01" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                    </div>
                  </div>
                  <div v-if="billingForm.discount_scope === 'procedure'" class="mt-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Discount Type</label>
                    <select v-model="procedure.discount_type" @change="updateProcedureTotal(index)" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900">
                      <option value="">None</option>
                      <option value="percentage">Percentage</option>
                      <option value="amount">Exact Amount</option>
                    </select>
                    <input v-if="procedure.discount_type" v-model.number="procedure.discount_value" type="number" min="0" step="0.01" :placeholder="procedure.discount_type === 'percentage' ? '0-100%' : 'Amount in ₱'" class="w-full border border-gray-300 rounded px-3 py-2 mt-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" @input="updateProcedureTotal(index)" />
                  </div>
                  <div class="flex justify-between items-center mt-2">
                    <span class="font-semibold">Total: ₱{{ procedure.total.toLocaleString() }}</span>
                    <button v-if="billingForm.procedures.length > 1" @click="removeProcedure(index)" class="text-red-600 hover:text-red-800 text-sm">Remove</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="mt-4">
              <button v-if="!showDiscount" @click="showDiscount = true" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">Add Discount</button>
              <div v-else>
                <div class="flex justify-between items-center mb-2">
                  <div class="flex items-center gap-2">
                    <label class="block text-sm font-medium text-gray-700">Apply Discount to</label>
                    <div class="flex items-center gap-4">
                      <label><input v-model="billingForm.discount_scope" type="radio" value="whole" class="mr-1"> Whole Billing</label>
                      <label><input v-model="billingForm.discount_scope" type="radio" value="procedure" class="mr-1"> Specific Procedure</label>
                    </div>
                  </div>
                  <button @click="removeDiscount" class="text-red-600 hover:text-red-800 text-sm">Remove Discount</button>
                </div>
                <div v-if="billingForm.discount_scope === 'whole'">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Discount Type</label>
                  <select v-model="billingForm.discount_type" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900">
                    <option value="">None</option>
                    <option value="percentage">Percentage</option>
                    <option value="amount">Exact Amount</option>
                  </select>
                  <input v-if="billingForm.discount_type" v-model.number="billingForm.discount_value" type="number" min="0" step="0.01" :placeholder="billingForm.discount_type === 'percentage' ? '0-100%' : 'Amount in ₱'" class="w-full border border-gray-300 rounded px-3 py-2 mt-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
                </div>
              </div>
            </div>
            <div class="text-right">
              <label class="block text-sm font-medium text-gray-700 mb-1">Total Amount</label>
              <input type="text" :value="`₱${getTotal.toLocaleString()}`" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50 font-semibold text-lg text-center" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
              <textarea v-model="billingForm.discount_reason" rows="3" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"></textarea>
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