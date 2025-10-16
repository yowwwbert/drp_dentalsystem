<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { watch } from 'vue';

// TypeScript interfaces
interface Payment {
  payment_id: string;
  billing_id: string;
  appointment_id: string;
  patient_id: string;
  patient_name: string;
  payment_method_id: string;
  payment_method_name: string;
  amount: number;
  payment_date: string;
  status: string;
  payment_type: 'Partial' | 'Full' | 'Advance';
  notes: string;
  handled_by: string;
  appointment_details: { appointment_id: string; schedule_date: string; start_time: string; services: string[]; status: string; patient_name: string } | null;
}

interface Billing {
  billing_id: string;
  patient_name: string;
  patient_id: string;
  amount: number;
  appointments: { appointment_id: string; date: string; time: string; services: string[]; status: string; patient_name: string }[];
}

interface PaymentMethod {
  payment_method_id: string;
  payment_method_name: string;
}

interface Branch {
  branch_id: string;
  branch_name: string;
}

interface User {
  user_id: string;
  first_name: string;
  last_name: string;
  user_type: string;
  branch: Branch | null;
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Payments Management', href: '/dashboard/billing/payments'},
];

const searchQuery = ref('');
const currentPage = ref(1);
const itemsPerPage = ref(10);

const page = usePage<{ auth: { user: User | null } }>();
const user = computed(() => page.props.auth.user);
const userFirstName = computed(() => user.value?.first_name || 'User');
const userPosition = computed(() => user.value?.user_type || 'User');
const userId = computed(() => user.value?.user_id || '');
const isPatient = computed(() => userPosition.value === 'Patient');
const canModify = computed(() => !isPatient.value);

// Modal states
const showViewModal = ref(false);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showConfirmCreateModal = ref(false);
const selectedPayment = ref<Payment | null>(null);

// Form data for create/edit payment modal
const paymentForm = ref({
  payment_id: '',
  billing_id: '',
  appointment_id: '',
  patient_id: '',
  payment_method_id: '',
  amount: 0,
  payment_date: new Date().toISOString().split('T')[0],
  status: 'Completed',
  payment_type: 'Full' as 'Partial' | 'Full' | 'Advance',
  notes: '',
  handled_by: '',
});

// Billing, Appointments, and Payment Methods data
const billings = ref<Billing[]>([]);
const billingAppointments = ref<{ appointment_id: string; date: string; time: string; services: string[]; status: string; patient_name: string }[]>([]);
const paymentMethods = ref<PaymentMethod[]>([]);
const payments = ref<Payment[]>([]);

const fetchPayments = async () => {
  try {
    let response;
    if (isPatient.value) {
      // Fetch payments for the specific patient using patient_id
      response = await axios.get(`/api/payments?patient_id=${userId.value}`);
    } else {
      // Fetch all payments for owner
      response = await axios.get('/api/payments');
    }
    payments.value = response.data.data;
  } catch (error) {
    console.error('Error fetching payments:', error);
    alert('Failed to fetch payments: ' + (error.response?.data?.error || 'Unknown error'));
  }
};

const fetchPaymentById = async (paymentId: string) => {
  try {
    const payment = payments.value.find((p: Payment) => p.payment_id === paymentId);
    if (!payment) throw new Error('Payment not found');
    return payment;
  } catch (error) {
    console.error('Error fetching payment:', error);
    alert('Failed to fetch payment: ' + (error.message || 'Unknown error'));
    return null;
  }
};

const fetchBillings = async () => {
  try {
    let response;
    if (isPatient.value) {
      // Fetch billings for the specific patient
      response = await axios.get(`/api/billings?patient_id=${userId.value}`);
    } else {
      // Fetch all billings for owner
      response = await axios.get('/api/billings');
    }
    billings.value = response.data.data;
  } catch (error) {
    console.error('Error fetching billings:', error);
    alert('Failed to fetch billings: ' + (error.response?.data?.error || 'Unknown error'));
  }
};

const fetchPaymentMethods = async () => {
  try {
    const response = await axios.get('/api/payment-methods');
    paymentMethods.value = response.data.data;
  } catch (error) {
    console.error('Error fetching payment methods:', error);
    alert('Failed to fetch payment methods: ' + (error.response?.data?.error || 'Unknown error'));
  }
};

onMounted(() => {
  fetchPayments();
  fetchBillings();
  fetchPaymentMethods();
});

watch(() => paymentForm.value.billing_id, (newBillingId) => {
  if (newBillingId) {
    const selectedBilling = billings.value.find(b => b.billing_id === newBillingId);
    if (selectedBilling) {
      paymentForm.value.patient_id = selectedBilling.patient_id;
      billingAppointments.value = selectedBilling.appointments || [];
      if (paymentForm.value.appointment_id && !billingAppointments.value.some(a => a.appointment_id === paymentForm.value.appointment_id)) {
        paymentForm.value.appointment_id = '';
      }
    } else {
      paymentForm.value.patient_id = '';
      paymentForm.value.appointment_id = '';
      billingAppointments.value = [];
    }
  } else {
    paymentForm.value.patient_id = '';
    paymentForm.value.appointment_id = '';
    billingAppointments.value = [];
  }
});

const filteredPaymentRecords = computed(() => {
  let result = payments.value;
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    result = result.filter(payment =>
      payment.patient_name.toLowerCase().includes(query) ||
      payment.payment_id.toLowerCase().includes(query) ||
      payment.billing_id.toLowerCase().includes(query)
    );
  }
  return result;
});

const paginatedPaymentRecords = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value;
  const end = start + itemsPerPage.value;
  return filteredPaymentRecords.value.slice(start, end);
});

const totalPages = computed(() => {
  return Math.max(1, Math.ceil(filteredPaymentRecords.value.length / itemsPerPage.value));
});

const remainingBalance = computed(() => {
  if (!paymentForm.value.billing_id) return null;
  const billing = billings.value.find(b => b.billing_id === paymentForm.value.billing_id);
  if (!billing) return null;
  const completedPayments = payments.value
    .filter(p => p.billing_id === paymentForm.value.billing_id && p.status.toLowerCase() === 'completed' && p.payment_type.toLowerCase() !== 'refunded')
    .reduce((sum, p) => sum + p.amount, 0);
  return billing.amount - completedPayments;
});

const handleSearch = () => {
  currentPage.value = 1;
};

const handlePageChange = (page: number) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page;
  }
};

const handleViewPayment = async (paymentId: string) => {
  const payment = await fetchPaymentById(paymentId);
  selectedPayment.value = payment || null;
  showViewModal.value = !!payment;
};

const handleCreatePayment = () => {
  paymentForm.value = {
    payment_id: '',
    billing_id: '',
    appointment_id: '',
    patient_id: '',
    payment_method_id: '',
    amount: 0,
    payment_date: new Date().toISOString().split('T')[0],
    status: 'Completed',
    payment_type: 'Full',
    notes: '',
    handled_by: userId.value,
  };
  billingAppointments.value = [];
  showCreateModal.value = true;
};

const handleEditPayment = async (paymentId: string) => {
  const payment = await fetchPaymentById(paymentId);
  if (payment) {
    paymentForm.value = {
      payment_id: payment.payment_id,
      billing_id: payment.billing_id,
      appointment_id: payment.appointment_id,
      patient_id: payment.patient_id,
      payment_method_id: payment.payment_method_id,
      amount: payment.amount,
      payment_date: payment.payment_date.split('T')[0],
      status: payment.status,
      payment_type: payment.payment_type,
      notes: payment.notes,
      handled_by: payment.handled_by,
    };
    const selectedBilling = billings.value.find(b => b.billing_id === payment.billing_id);
    billingAppointments.value = selectedBilling ? selectedBilling.appointments || [] : [];
    showEditModal.value = true;
  }
};

const closeViewModal = () => {
  showViewModal.value = false;
  selectedPayment.value = null;
};

const closeCreateModal = () => {
  showCreateModal.value = false;
};

const closeEditModal = () => {
  showEditModal.value = false;
};

const closeConfirmCreateModal = () => {
  showConfirmCreateModal.value = false;
};

const showConfirmation = () => {
  showCreateModal.value = false;
  showConfirmCreateModal.value = true;
};

const createPayment = async () => {
  try {
    const { payment_id, ...payload } = paymentForm.value;
    await axios.post('/api/payments', {
      ...payload,
      handled_by: userId.value,
    });
    await fetchPayments();
    closeConfirmCreateModal();
  } catch (error) {
    console.error('Error creating payment:', error);
    alert('Failed to create payment: ' + (error.response?.data?.error || 'Unknown error'));
  }
};

const savePayment = async () => {
  try {
    const { payment_id, ...payload } = paymentForm.value;
    await axios.put(`/api/payments/${paymentForm.value.payment_id}`, {
      ...payload,
      handled_by: userId.value,
    });
    await fetchPayments();
    closeEditModal();
  } catch (error) {
    console.error('Error updating payment:', error);
    alert('Failed to update payment: ' + (error.response?.data?.error || 'Unknown error'));
  }
};

const formatDate = (dateStr: string) => {
  if (!dateStr || isNaN(Date.parse(dateStr))) return 'N/A';
  const date = new Date(dateStr);
  return date.toLocaleDateString('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
    timeZone: 'America/Los_Angeles',
  });
};

const formatStatus = (status: string) => {
  if (!status) return 'N/A';
  return status.charAt(0).toUpperCase() + status.slice(1).toLowerCase();
};

const getStatusColor = (status: string) => {
  switch (status.toLowerCase()) {
    case 'completed': return 'bg-green-100 text-green-800';
    case 'pending': return 'bg-yellow-100 text-yellow-800';
    case 'failed': return 'bg-red-100 text-red-800';
    case 'refunded': return 'bg-gray-100 text-gray-800';
    default: return 'bg-gray-100 text-gray-800';
  }
};

const isAppointmentDisabled = computed(() => {
  return !paymentForm.value.billing_id || billingAppointments.value.length === 0;
});

const selectedAppointmentDetails = computed(() => {
  if (!paymentForm.value.appointment_id) return null;
  return billingAppointments.value.find(a => a.appointment_id === paymentForm.value.appointment_id);
});

const selectedBillingDetails = computed(() => {
  if (!paymentForm.value.billing_id) return null;
  return billings.value.find(b => b.billing_id === paymentForm.value.billing_id);
});
</script>

<template>
  <Head title="Payments Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-4 rounded-xl p-4 bg-gray-50 min-h-screen relative">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-2 gap-4" :class="{ 'blur-[1px]': showViewModal || showCreateModal || showEditModal || showConfirmCreateModal }">
        <h1 class="text-3xl font-bold text-gray-900">Payments Management</h1>
        <div class="flex flex-1 justify-end items-center gap-2">
          <input
            v-model="searchQuery"
            @input="handleSearch"
            type="text"
            placeholder="Search by patient name, payment ID, or billing ID..."
            class="border border-gray-300 rounded px-4 py-2 w-80 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
          />
        </div>
      </div>
      <div v-if="canModify" class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-2 gap-4" :class="{ 'blur-[1px]': showViewModal || showCreateModal || showEditModal || showConfirmCreateModal }">
        <div class="flex gap-2">
          <button
            @click="handleCreatePayment"
            class="bg-darkGreen-900 text-white px-6 py-2 rounded font-semibold shadow hover:bg-darkGreen-800 transition"
          >Create Payment</button>
        </div>
      </div>
      <div class="bg-white rounded-xl shadow p-0 overflow-x-auto" :class="{ 'blur-[1px]': showViewModal || showCreateModal || showEditModal || showConfirmCreateModal }">
        <table class="w-full min-w-[800px] border-separate border-spacing-0">
          <thead>
            <tr class="bg-darkGreen-900 text-white">
              <th class="py-3 px-4 text-left font-semibold">Payment ID</th>
              <th class="py-3 px-4 text-left font-semibold">Patient Name</th>
              <th class="py-3 px-4 text-left font-semibold">Amount Paid</th>
              <th class="py-3 px-4 text-left font-semibold">Payment Method</th>
              <th class="py-3 px-4 text-left font-semibold">Payment Type</th>
              <th class="py-3 px-4 text-left font-semibold">Status</th>
              <th class="py-3 px-4 text-left font-semibold">Handled By</th>
              <th class="py-3 px-4 text-left font-semibold">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="paginatedPaymentRecords.length === 0">
              <td colspan="9" class="text-center py-8 text-gray-500 text-lg">No payment records found.</td>
            </tr>
            <tr v-for="payment in paginatedPaymentRecords" :key="payment.payment_id" class="border-b last:border-b-0 hover:bg-gray-50">
              <td class="py-3 px-4 font-medium">
                <div>{{ payment.payment_id }}</div>
                <div class="text-sm text-gray-500">Billing: {{ payment.billing_id }}</div>
              </td>
              <td class="py-3 px-4">{{ payment.patient_name }}</td>
              <td class="py-3 px-4 font-semibold text-green-600">₱{{ payment.amount.toLocaleString() }}</td>
              <td class="py-3 px-4">{{ payment.payment_method_name }}</td>
              <td class="py-3 px-4">{{ formatStatus(payment.payment_type) }} Payment</td>
              <td class="py-3 px-4">
                <span :class="`px-2 py-1 rounded-full text-sm font-medium ${getStatusColor(payment.status)}`">
                  {{ formatStatus(payment.status) }}
                </span>
              </td>
              <td class="py-3 px-4">
                <div>{{ payment.handled_by }}</div>
                <div class="text-xs text-gray-500">
                  {{ formatDate(payment.payment_date) }}
                </div>
              </td>
              <td class="py-3 px-4">
                <div class="flex gap-2">
                  <button
                    @click="handleViewPayment(payment.payment_id)"
                    class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition text-sm"
                  >View</button>
                  <button
                    v-if="canModify"
                    @click="handleEditPayment(payment.payment_id)"
                    class="bg-darkGreen-900 text-white px-3 py-1 rounded hover:bg-darkGreen-800 transition text-sm"
                  >Edit</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mt-4 gap-4" :class="{ 'blur-[1px]': showViewModal || showCreateModal || showEditModal || showConfirmCreateModal }">
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

      <!-- View Payment Modal -->
      <div v-if="showViewModal" class="absolute inset-0 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
          <h2 class="text-xl font-bold text-gray-900 mb-4">View Payment Details</h2>
          <div v-if="selectedPayment" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Payment ID</label>
                <input type="text" :value="selectedPayment.payment_id" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Billing ID</label>
                <input type="text" :value="selectedPayment.billing_id" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Patient Name</label>
                <input type="text" :value="selectedPayment.patient_name" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                <input type="text" :value="selectedPayment.payment_method_name" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                <input type="text" :value="`₱${selectedPayment.amount.toLocaleString()}`" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Date</label>
                <input type="text" :value="formatDate(selectedPayment.payment_date)" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Type</label>
                <input type="text" :value="selectedPayment.payment_type" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <input type="text" :value="formatStatus(selectedPayment.status)" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
              </div>
            </div>
            <div class="mt-4">
              <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
              <textarea :value="selectedPayment.notes || ''" readonly rows="3" class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50 resize-none"></textarea>
            </div>
            <div v-if="selectedPayment.appointment_details" class="mt-4">
              <label class="block text-sm font-medium text-gray-700 mb-1">Appointment Details</label>
              <div class="bg-gray-50 p-4 rounded border">
                <p>Appointment ID: {{ selectedPayment.appointment_details.appointment_id }}</p>
                <p>Date: {{ formatDate(selectedPayment.appointment_details.schedule_date) }}</p>
                <p>Time: {{ selectedPayment.appointment_details.start_time }}</p>
                <p>Services: {{ selectedPayment.appointment_details.services.join(', ') }}</p>
                <p>Status: {{ formatStatus(selectedPayment.appointment_details.status) }}</p>
                <p>Patient: {{ selectedPayment.appointment_details.patient_name }}</p>
              </div>
            </div>
          </div>
          <div class="flex justify-end gap-3 mt-6">
            <button @click="closeViewModal" class="px-4 py-2 text-gray-700 bg-gray-200 rounded hover:bg-gray-300 transition">Close</button>
          </div>
        </div>
      </div>

      <!-- Create Payment Modal -->
      <div v-if="showCreateModal" class="absolute inset-0 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
          <h2 class="text-xl font-bold text-gray-900 mb-4">Create New Payment</h2>
          <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Select Billing</label>
                <select v-model="paymentForm.billing_id" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900">
                  <option value="">Select a billing</option>
                  <option v-for="billing in billings" :key="billing.billing_id" :value="billing.billing_id">
                    {{ billing.patient_name }} - {{ billing.billing_id }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Remaining Balance</label>
                <input
                  type="text"
                  :value="remainingBalance ? `₱${remainingBalance.toLocaleString()}` : 'Select a billing'"
                  readonly
                  class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Select Appointment</label>
                <select
                  v-model="paymentForm.appointment_id"
                  class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                  :disabled="isAppointmentDisabled"
                >
                  <option value="">Select an appointment</option>
                  <option v-for="appointment in billingAppointments" :key="appointment.appointment_id" :value="appointment.appointment_id">
                    {{ appointment.appointment_id }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Select Payment Method</label>
                <select v-model="paymentForm.payment_method_id" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900">
                  <option value="">Select a method</option>
                  <option v-for="method in paymentMethods" :key="method.payment_method_id" :value="method.payment_method_id">
                    {{ method.payment_method_name }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                <input v-model.number="paymentForm.amount" type="number" min="0" step="0.01" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Date</label>
                <input v-model="paymentForm.payment_date" type="date" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Type</label>
                <select v-model="paymentForm.payment_type" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900">
                  <option value="Full">Full</option>
                  <option value="Partial">Partial</option>
                  <option value="Advance">Advance</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select v-model="paymentForm.status" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900">
                  <option value="Pending">Pending</option>
                  <option value="Completed">Completed</option>
                  <option value="Failed">Failed</option>
                  <option value="Refunded">Refunded</option>
                </select>
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
              <textarea v-model="paymentForm.notes" rows="3" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900 resize-none"></textarea>
            </div>
          </div>
          <div class="flex justify-end gap-3 mt-6">
            <button @click="closeCreateModal" class="px-4 py-2 text-gray-700 bg-gray-200 rounded hover:bg-gray-300 transition">Cancel</button>
            <button @click="showConfirmation" class="px-4 py-2 text-white bg-darkGreen-900 rounded hover:bg-darkGreen-800 transition">Create Payment</button>
          </div>
        </div>
      </div>

      <!-- Confirm Create Payment Modal -->
      <div v-if="showConfirmCreateModal" class="absolute inset-0 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
          <h2 class="text-xl font-bold text-gray-900 mb-4">Confirm Payment Creation</h2>
          <div class="space-y-6">
            <div v-if="selectedAppointmentDetails" class="mt-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Appointment Details</label>
              <div class="bg-gray-50 p-4 rounded border grid grid-cols-2 gap-x-4 gap-y-2">
                <div class="font-medium text-gray-700">Appointment ID:</div>
                <div>{{ selectedAppointmentDetails.appointment_id }}</div>
                <div class="font-medium text-gray-700">Date:</div>
                <div>{{ formatDate(selectedAppointmentDetails.date) }}</div>
                <div class="font-medium text-gray-700">Time:</div>
                <div>{{ selectedAppointmentDetails.time }}</div>
                <div class="font-medium text-gray-700">Services:</div>
                <div>{{ selectedAppointmentDetails.services.join(', ') }}</div>
                <div class="font-medium text-gray-700">Status:</div>
                <div>{{ formatStatus(selectedAppointmentDetails.status) }}</div>
                <div class="font-medium text-gray-700">Patient:</div>
                <div>{{ selectedAppointmentDetails.patient_name }}</div>
              </div>
            </div>
            <div v-else class="mt-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Appointment Details</label>
              <div class="bg-gray-50 p-4 rounded border text-center text-gray-500">
                No appointment selected
              </div>
            </div>
            <div v-if="selectedBillingDetails" class="mt-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Billing Details</label>
              <div class="bg-gray-50 p-4 rounded border grid grid-cols-2 gap-x-4 gap-y-2">
                <div class="font-medium text-gray-700">Billing ID:</div>
                <div>{{ selectedBillingDetails.billing_id }}</div>
                <div class="font-medium text-gray-700">Patient:</div>
                <div>{{ selectedBillingDetails.patient_name }}</div>
                <div class="font-medium text-gray-700">Original Amount:</div>
                <div>₱{{ selectedBillingDetails.amount.toLocaleString() }}</div>
                <div class="font-medium text-gray-700">Remaining Balance:</div>
                <div>₱{{ remainingBalance ? remainingBalance.toLocaleString() : 'N/A' }}</div>
              </div>
            </div>
            <div class="mt-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Payment Details</label>
              <div class="bg-gray-50 p-4 rounded border grid grid-cols-2 gap-x-4 gap-y-2">
                <div class="font-medium text-gray-700">Amount:</div>
                <div>₱{{ paymentForm.amount.toLocaleString() }}</div>
                <div class="font-medium text-gray-700">Payment Date:</div>
                <div>{{ formatDate(paymentForm.payment_date) }}</div>
                <div class="font-medium text-gray-700">Payment Type:</div>
                <div>{{ paymentForm.payment_type }}</div>
                <div class="font-medium text-gray-700">Status:</div>
                <div>{{ formatStatus(paymentForm.status) }}</div>
                <div class="font-medium text-gray-700">Payment Method:</div>
                <div>{{ paymentMethods.find(m => m.payment_method_id === paymentForm.payment_method_id)?.payment_method_name || 'N/A' }}</div>
                <div class="font-medium text-gray-700">Handled By:</div>
                <div>{{ userId }}</div>
                <div class="font-medium text-gray-700">Notes:</div>
                <div>{{ paymentForm.notes || 'N/A' }}</div>
              </div>
            </div>
          </div>
          <div class="flex justify-end gap-3 mt-6">
            <button @click="closeConfirmCreateModal" class="px-4 py-2 text-gray-700 bg-gray-200 rounded hover:bg-gray-300 transition">Cancel</button>
            <button @click="createPayment" class="px-4 py-2 text-white bg-darkGreen-900 rounded hover:bg-darkGreen-800 transition">Confirm</button>
          </div>
        </div>
      </div>

      <!-- Edit Payment Modal -->
      <div v-if="showEditModal" class="absolute inset-0 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
          <h2 class="text-xl font-bold text-gray-900 mb-4">Edit Payment</h2>
          <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Payment ID</label>
                <input v-model="paymentForm.payment_id" type="text" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Select Billing</label>
                <select v-model="paymentForm.billing_id" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900">
                  <option value="">Select a billing</option>
                  <option v-for="billing in billings" :key="billing.billing_id" :value="billing.billing_id">
                    {{ billing.patient_name }} - {{ billing.billing_id }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Remaining Balance</label>
                <input
                  type="text"
                  :value="remainingBalance ? `₱${remainingBalance.toLocaleString()}` : 'Select a billing'"
                  readonly
                  class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Select Appointment</label>
                <select
                  v-model="paymentForm.appointment_id"
                  class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                  :disabled="isAppointmentDisabled"
                >
                  <option value="">Select an appointment</option>
                  <option v-for="appointment in billingAppointments" :key="appointment.appointment_id" :value="appointment.appointment_id">
                    {{ appointment.appointment_id }} - {{ appointment.patient_name }} - {{ formatDate(appointment.date) }} {{ appointment.time }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Select Payment Method</label>
                <select v-model="paymentForm.payment_method_id" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900">
                  <option value="">Select a method</option>
                  <option v-for="method in paymentMethods" :key="method.payment_method_id" :value="method.payment_method_id">
                    {{ method.payment_method_name }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                <input v-model.number="paymentForm.amount" type="number" min="0" step="0.01" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Date</label>
                <input v-model="paymentForm.payment_date" type="date" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Type</label>
                <select v-model="paymentForm.payment_type" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900">
                  <option value="Full">Full</option>
                  <option value="Partial">Partial</option>
                  <option value="Advance">Advance</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select v-model="paymentForm.status" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900">
                  <option value="Pending">Pending</option>
                  <option value="Completed">Completed</option>
                  <option value="Failed">Failed</option>
                  <option value="Refunded">Refunded</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Handled By</label>
                <input
                  type="text"
                  :value="paymentForm.handled_by"
                  readonly
                  class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50"
                />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
              <textarea v-model="paymentForm.notes" rows="3" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900 resize-none"></textarea>
            </div>
          </div>
          <div class="flex justify-end gap-3 mt-6">
            <button @click="closeEditModal" class="px-4 py-2 text-gray-700 bg-gray-200 rounded hover:bg-gray-300 transition">Cancel</button>
            <button @click="savePayment" class="px-4 py-2 text-white bg-darkGreen-900 rounded hover:bg-darkGreen-800 transition">Save Changes</button>
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