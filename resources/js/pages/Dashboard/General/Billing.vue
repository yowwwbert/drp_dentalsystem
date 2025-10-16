<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

// TypeScript interfaces
interface Treatment {
  treatment_id: string;
  treatment_name: string;
  dentist_id: string;
  dentist_name?: string; // Optional, for display only
  description: string;
  price: number;
  discount_amount: number;
  discount_reason?: string;
  total: number;
}

interface Billing {
  billing_id: string;
  patient_id: string;
  patient_name: string;
  billing_date: string;
  amount: number;
  status: string;
  discount_amount: number;
  discount_reason: string;
  treatments: Treatment[];
  appointments: {
    appointment_id: string;
    date: string;
    start_time: string;
    status: string;
    branch_id: string;
    branch_name: string;
    services: string[];
  }[];
}

interface Appointment {
  appointment_id: string;
  patient_id: string;
  patient_first_name: string;
  patient_last_name: string;
  date: string;
  start_time: string;
  status: string;
  services: string[];
  billing_id: string | null;
  branch_id: string;
  branch_name: string;
  dentist_id: string;
}

interface TreatmentOption {
  treatment_id: string;
  treatment_name: string;
  base_price: number;
}

interface Dentist {
  dentist_id: string;
  dentist_type: string;
  first_name: string;
  last_name: string;
  email_address: string;
  phone_number: string;
  user_id: string;
  position: string;
  status: string;
  branch_name: string | null;
  branch_id: string | null;
}

interface User {
  user_id: string;
  first_name: string;
  last_name: string;
  user_type: string;
  branch_id?: string;
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Billing Management', href: '/billing' },
];

const searchQuery = ref('');
const appointmentSearchQuery = ref('');
const selectedStatus = ref('All Status');
const currentPage = ref(1);
const itemsPerPage = ref(10);

// Modal states
const showViewModal = ref(false);
const showCreateBillingModal = ref(false);
const selectedBilling = ref<Billing | null>(null);

// Form data for create billing modal
const billingForm = ref({
  patient_id: '',
  patient_name: '',
  appointment_ids: [] as string[],
  amount: 0,
  status: 'Pending',
  discount_amount: 0,
  discount_reason: '',
  treatments: [] as Treatment[],
});

// Data sources
const appointments = ref<Appointment[]>([]);
const treatments = ref<TreatmentOption[]>([]);
const dentists = ref<Dentist[]>([]);
const billings = ref<Billing[]>([]);
const totalRecords = ref(0);

const page = usePage<{ auth: { user: User | null } }>();
const user = computed(() => page.props.auth?.user);
const userType = computed(() => user.value?.user_type || 'Unknown');
const userId = computed(() => user.value?.user_id || '');
const userBranchId = computed(() => user.value?.branch_id || null);
const isPatient = computed(() => userType.value === 'Patient');
const canModify = computed(() => !isPatient.value);

const fetchBillings = async () => {
  try {
    const params: Record<string, string> = {};
    if (userType.value === 'Patient') {
      params.patient_id = userId.value;
    } else if (userType.value === 'Dentist') {
      params.dentist_id = userId.value;
    } else if (userType.value === 'Staff' && userBranchId.value) {
      params.branch_id = userBranchId.value;
    }
    const response = await axios.get('/api/billings', { params });
    billings.value = response.data.data;
    totalRecords.value = response.data.total_records;
    console.log('Fetched billings:', billings.value);
  } catch (error: any) {
    console.error('Error fetching billings:', error);
    alert('Failed to fetch billings: ' + (error.response?.data?.error || 'Unknown error'));
  }
};

const fetchBillingById = async (billingId: string) => {
  try {
    const billing = billings.value.find((b: Billing) => b.billing_id === billingId);
    if (!billing) throw new Error('Billing not found');
    // Populate dentist_name for display in View Billing Modal
    billing.treatments.forEach(treatment => {
      treatment.dentist_name = getDentistName(treatment.dentist_id);
    });
    return billing;
  } catch (error: any) {
    console.error('Error fetching billing:', error);
    alert('Failed to fetch billing: ' + (error.message || 'Unknown error'));
    return null;
  }
};

const fetchTreatments = async () => {
  try {
    const response = await axios.get('/dashboard/clinic/api/treatments');
    treatments.value = Array.isArray(response.data.data) 
      ? response.data.data.map((t: any) => ({
          treatment_id: t.treatment_id,
          treatment_name: t.treatment_name,
          base_price: t.base_price || 0,
        }))
      : Array.isArray(response.data)
      ? response.data.map((t: any) => ({
          treatment_id: t.treatment_id,
          treatment_name: t.treatment_name,
          base_price: t.base_price || 0,
        }))
      : [];
    console.log('Fetched treatments:', treatments.value);
  } catch (error: any) {
    console.error('Error fetching treatments:', error);
    alert('Failed to fetch treatments: ' + (error.response?.data?.error || 'Unknown error'));
  }
};

const fetchDentists = async () => {
  try {
    const params: Record<string, string> = {};
    if (userType.value === 'Staff' && userBranchId.value) {
      params.branch_id = userBranchId.value;
    }
    const response = await axios.get('/dashboard/owner/api/dentists', { params });
    dentists.value = Array.isArray(response.data.dentists) 
      ? response.data.dentists 
      : [];
    console.log('Fetched dentists:', dentists.value);
  } catch (error: any) {
    console.error('Error fetching dentists:', error);
    alert('Failed to fetch dentists: ' + (error.response?.data?.error || 'Unknown error'));
  }
};

const fetchAppointments = async () => {
  try {
    const params: Record<string, string> = {};
    if (userType.value === 'Patient') {
      params.patient_id = userId.value;
    } else if (userType.value === 'Dentist') {
      params.dentist_id = userId.value;
    } else if (userType.value === 'Staff' && userBranchId.value) {
      params.branch_id = userBranchId.value;
    }
    const response = await axios.get('/dashboard/appointments', { params });
    const validAppointments = response.data.appointments
      .filter((appointment: Appointment) => {
        return ['Scheduled', 'Checked In', 'Completed'].includes(appointment.status) &&
          appointment.billing_id === null;
      })
      .map((appointment: any) => ({
        ...appointment,
        patient_first_name: appointment.patient_first_name || 'Unknown',
        patient_last_name: appointment.patient_last_name || '',
        start_time: appointment.start_time || 'N/A',
        services: Array.isArray(appointment.services) ? appointment.services : ['General Checkup'],
        dentist_id: appointment.dentist_id || '',
        branch_name: appointment.branch_name || 'N/A',
      }));
    appointments.value = validAppointments;
    console.log('Fetched appointments:', validAppointments);
  } catch (error: any) {
    console.error('Error fetching appointments:', error);
    alert('Failed to fetch appointments: ' + (error.response?.data?.message || 'Unknown error'));
  }
};

// Computed property for filtered appointments
const filteredAppointments = computed(() => {
  if (!appointmentSearchQuery.value) return appointments.value;

  const query = appointmentSearchQuery.value.toLowerCase();
  return appointments.value.filter(appointment => {
    const patientName = [appointment.patient_first_name, appointment.patient_last_name]
      .filter(Boolean)
      .join(' ')
      .toLowerCase();
    const date = formatDateTime(appointment.date, 'datetime', appointment.start_time).toLowerCase();
    return patientName.includes(query) || date.includes(query);
  });
});

// Handle appointment selection
const onAppointmentSelect = () => {
  addAppointments();
};

// Function to add selected appointments and their treatments
const addAppointments = () => {
  if (!billingForm.value.appointment_ids.length) {
    clearAppointments();
    return;
  }
  const selectedAppointments = appointments.value.filter(a => billingForm.value.appointment_ids.includes(a.appointment_id));
  if (selectedAppointments.length) {
    const patientIds = [...new Set(selectedAppointments.map(a => a.patient_id))];
    if (patientIds.length > 1) {
      alert('All selected appointments must belong to the same patient.');
      billingForm.value.appointment_ids = [];
      billingForm.value.treatments = [];
      calculateTotals();
      return;
    }
    // Set patient details
    billingForm.value.patient_id = patientIds[0];
    billingForm.value.patient_name = selectedAppointments[0].patient_first_name + ' ' + selectedAppointments[0].patient_last_name;

    // Clear existing treatments to avoid duplicates
    billingForm.value.treatments = [];

    // Populate treatments from appointment services
    const treatmentsSet = new Set<string>();
    const newTreatments: Treatment[] = [];
    selectedAppointments.forEach(appointment => {
      (appointment.services || ['General Checkup']).forEach((service: string) => {
        if (!treatmentsSet.has(service.toLowerCase())) {
          treatmentsSet.add(service.toLowerCase());
          const treatmentOption = treatments.value.find(t => t.treatment_name.toLowerCase() === service.toLowerCase()) || {
            treatment_id: '',
            treatment_name: service,
            base_price: 0,
          };
          newTreatments.push({
            treatment_id: treatmentOption.treatment_id,
            treatment_name: treatmentOption.treatment_name,
            dentist_id: appointment.dentist_id || '',
            dentist_name: getDentistName(appointment.dentist_id),
            description: `Dental ${treatmentOption.treatment_name.toLowerCase()} procedure`,
            price: treatmentOption.base_price || 0,
            discount_amount: 0,
            total: treatmentOption.base_price || 0,
          });
        }
      });
    });

    // Set treatments or fallback to a single empty treatment
    billingForm.value.treatments = newTreatments.length > 0 ? newTreatments : [{
      treatment_id: '',
      treatment_name: '',
      dentist_id: '',
      dentist_name: '',
      description: '',
      price: 0,
      discount_amount: 0,
      total: 0,
    }];

    calculateTotals();
  } else {
    console.error('Selected appointments not found:', billingForm.value.appointment_ids);
    alert('Selected appointments not found.');
    billingForm.value.appointment_ids = [];
    billingForm.value.treatments = [{
      treatment_id: '',
      treatment_name: '',
      dentist_id: '',
      dentist_name: '',
      description: '',
      price: 0,
      discount_amount: 0,
      total: 0,
    }];
    calculateTotals();
  }
};

// Function to clear appointments and treatments
const clearAppointments = () => {
  billingForm.value.appointment_ids = [];
  billingForm.value.patient_id = '';
  billingForm.value.patient_name = '';
  billingForm.value.treatments = [{
    treatment_id: '',
    treatment_name: '',
    dentist_id: '',
    dentist_name: '',
    description: '',
    price: 0,
    discount_amount: 0,
    total: 0,
  }];
  calculateTotals();
};

onMounted(async () => {
  if (canModify.value) {
    await Promise.all([fetchTreatments(), fetchDentists()]);
    await fetchAppointments();
  }
  await fetchBillings();
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
  if (!canModify.value) {
    console.log('Cannot create billing: user lacks permission', { userType: userType.value });
    alert('You do not have permission to create billings.');
    return;
  }
  billingForm.value = {
    patient_id: '',
    patient_name: '',
    appointment_ids: [],
    amount: 0,
    status: 'Pending',
    discount_amount: 0,
    discount_reason: '',
    treatments: [],
  };
  showCreateBillingModal.value = true;
};

const closeViewModal = () => {
  showViewModal.value = false;
  selectedBilling.value = null;
};

const closeCreateBillingModal = () => {
  showCreateBillingModal.value = false;
};

const addTreatment = () => {
  let defaultDentistId = '';
  if (billingForm.value.appointment_ids.length > 0) {
    const dentistIds = [...new Set(billingForm.value.appointment_ids.map(id => appointments.value.find(a => a.appointment_id === id)?.dentist_id).filter(Boolean))];
    if (dentistIds.length === 1) {
      defaultDentistId = dentistIds[0] as string;
    }
  }
  billingForm.value.treatments.push({
    treatment_id: '',
    treatment_name: '',
    dentist_id: defaultDentistId,
    dentist_name: getDentistName(defaultDentistId),
    description: '',
    price: 0,
    discount_amount: 0,
    total: 0,
  });
  calculateTotals();
};

const removeTreatment = (index: number) => {
  if (billingForm.value.treatments.length > 1) {
    billingForm.value.treatments.splice(index, 1);
    calculateTotals();
  }
};

const onTreatmentSelect = (index: number) => {
  const treatment = billingForm.value.treatments[index];
  const selectedTreatment = treatments.value.find(t => t.treatment_id === treatment.treatment_id);
  if (selectedTreatment) {
    treatment.treatment_name = selectedTreatment.treatment_name;
    treatment.price = selectedTreatment.base_price || 0;
    treatment.description = `Dental ${selectedTreatment.treatment_name.toLowerCase()} procedure`;
    treatment.total = selectedTreatment.base_price || 0;
    calculateTotals();
  }
};

const updateTreatmentTotal = (index: number) => {
  const treatment = billingForm.value.treatments[index];
  treatment.total = (treatment.price || 0) - (treatment.discount_amount || 0);
  if (treatment.total < 0) treatment.total = 0;
  calculateTotals();
};

const calculateTotals = () => {
  const subtotal = billingForm.value.treatments.reduce((sum, t) => sum + (t.total || 0), 0);
  const wholeDiscount = billingForm.value.discount_amount || 0;
  billingForm.value.amount = subtotal - wholeDiscount;
};

const createBilling = async () => {
  if (!canModify.value) {
    console.log('Cannot create billing: user lacks permission', { userType: userType.value });
    alert('You do not have permission to create billings.');
    return;
  }
  if (!billingForm.value.patient_id || !billingForm.value.appointment_ids.length) {
    alert('Please select at least one appointment.');
    return;
  }
  if (billingForm.value.treatments.some(t => !t.treatment_id || !t.dentist_id)) {
    alert('Please select treatment and dentist for all treatments.');
    return;
  }
  try {
    const payload = {
      patient_id: billingForm.value.patient_id,
      billing_date: new Date().toISOString().split('T')[0],
      amount: billingForm.value.amount || 0,
      status: billingForm.value.status,
      discount_amount: billingForm.value.discount_amount || 0,
      discount_reason: billingForm.value.discount_reason || '',
      treatments: billingForm.value.treatments.map(t => ({
        treatment_id: t.treatment_id,
        dentist_id: t.dentist_id,
        description: t.description,
        price: t.price || 0,
        discount_amount: t.discount_amount || 0,
        discount_reason: t.discount_reason || '',
        total: t.total || 0,
      })),
      appointment_ids: billingForm.value.appointment_ids,
    };
    console.log('Creating billing with payload:', JSON.stringify(payload, null, 2));
    await axios.post('/api/billings', payload);
    await Promise.all([fetchBillings(), fetchAppointments()]);
    closeCreateBillingModal();
  } catch (error: any) {
    console.error('Error creating billing:', error);
    alert('Failed to create billing: ' + (error.response?.data?.error || 'Unknown error'));
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

const getDentistName = (dentistId: string): string => {
  if (!dentistId || !Array.isArray(dentists.value)) {
    console.warn('Invalid dentist_id or dentists.value is not an array:', { dentistId, dentists: dentists.value });
    return 'Unknown Dentist';
  }
  const dentist = dentists.value.find(d => d.dentist_id === dentistId);
  if (!dentist) {
    console.warn('Dentist not found for dentist_id:', dentistId);
    return 'Unknown Dentist';
  }
  return [dentist.first_name, dentist.last_name].filter(Boolean).join(' ') || 'Unknown Dentist';
};

const formatDateTime = (input: string, type: 'date' | 'time' | 'datetime' = 'date', timeInput?: string) => {
  if (!input || input === 'N/A') return 'N/A';
  try {
    if (type === 'date') {
      const dateOnly = input.split('T')[0];
      const date = new Date(dateOnly);
      if (isNaN(date.getTime())) return 'Invalid Date';
      return date.toLocaleString('en-PH', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
        timeZone: 'Asia/Manila',
      });
    } else if (type === 'time') {
      const date = new Date(`1970-01-01T${input}`);
      if (isNaN(date.getTime())) return 'Invalid Time';
      return date.toLocaleString('en-PH', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
        timeZone: 'Asia/Manila',
      });
    } else if (type === 'datetime' && timeInput) {
      const dateOnly = input.split('T')[0];
      const dateTimeString = `${dateOnly}T${timeInput}`;
      const date = new Date(dateTimeString);
      if (isNaN(date.getTime())) return 'Invalid DateTime';
      return date.toLocaleString('en-PH', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
        timeZone: 'Asia/Manila',
      }).replace(',', ' at ');
    }
    return 'Invalid Type';
  } catch (error) {
    console.error(`Error formatting ${type}:`, { input, timeInput, error });
    return type === 'time' ? 'Invalid Time' : 'Invalid Date';
  }
};

const formatCurrency = (value: number | null | undefined): string => {
  return `₱${(value ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};
</script>

<template>
  <Head title="Billing Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-4 rounded-xl p-4 bg-gray-50 dark:bg-neutral-900 min-h-screen relative">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-2 gap-4" :class="{ 'blur-[1px]': showViewModal || showCreateBillingModal }">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Billing Management</h1>
        <div class="flex flex-1 justify-end items-center gap-2">
          <input
            v-model="searchQuery"
            @input="handleSearch"
            type="text"
            placeholder="Search by patient name or billing ID..."
            class="border border-gray-300 dark:border-neutral-700 rounded px-4 py-2 w-80 bg-white dark:bg-neutral-750 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-darkGreen-900 dark:focus:ring-darkGreen-400"
          />
        </div>
      </div>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-2 gap-4" :class="{ 'blur-[1px]': showViewModal || showCreateBillingModal }">
        <div class="flex items-center gap-4">
          <div class="flex items-center gap-2">
            <label class="font-medium text-gray-700 dark:text-neutral-300 mr-2">Status</label>
            <select
              v-model="selectedStatus"
              @change="handleStatusFilter($event.target.value)"
              class="border border-gray-300 dark:border-neutral-700 rounded px-3 py-2 bg-white dark:bg-neutral-750 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-darkGreen-900 dark:focus:ring-darkGreen-400"
            >
              <option>All Status</option>
              <option value="Pending">Pending</option>
              <option value="Partially Paid">Partially Paid</option>
              <option value="Paid">Paid</option>
            </select>
          </div>
        </div>
        <div v-if="canModify" class="flex gap-2">
          <button
            @click="handleCreateBilling"
            class="bg-darkGreen-900 text-white px-6 py-2 rounded font-semibold shadow hover:bg-darkGreen-800 transition"
          >Create Billing</button>
        </div>
      </div>
      <div class="bg-white dark:bg-neutral-800 rounded-xl shadow p-0 overflow-x-auto" :class="{ 'blur-[1px]': showViewModal || showCreateBillingModal }">
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
              <td colspan="6" class="text-center py-8 text-gray-500 dark:text-neutral-400 text-lg">No billing records found.</td>
            </tr>
            <tr v-for="billing in paginatedBillingRecords" :key="billing.billing_id" class="border-b last:border-b-0 hover:bg-gray-50 dark:hover:bg-neutral-700">
              <td class="py-3 px-4 font-medium text-gray-900 dark:text-white">{{ billing.billing_id }}</td>
              <td class="py-3 px-4 text-gray-900 dark:text-white">{{ billing.patient_name }}</td>
              <td class="py-3 px-4 text-gray-900 dark:text-white">{{ formatDateTime(billing.billing_date, 'date') }}</td>
              <td class="py-3 px-4 font-semibold text-green-600 dark:text-green-400">{{ formatCurrency(billing.amount) }}</td>
              <td class="py-3 px-4">
                <span :class="`px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(billing.status)}`">
                  {{ billing.status }}
                </span>
              </td>
              <td class="py-3 px-4">
                <button
                  @click="handleViewBilling(billing.billing_id)"
                  class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition text-sm"
                >View</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mt-4 gap-4" :class="{ 'blur-[1px]': showViewModal || showCreateBillingModal }">
        <div class="flex items-center gap-2">
          <span class="text-sm text-gray-700 dark:text-neutral-300">Rows per page:</span>
          <select
            v-model="itemsPerPage"
            @change="currentPage = 1"
            class="border border-gray-300 dark:border-neutral-700 rounded px-2 py-1 text-sm bg-white dark:bg-neutral-750 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-darkGreen-900 dark:focus:ring-darkGreen-400"
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
            class="border border-gray-300 dark:border-neutral-700 rounded px-3 py-1 text-lg text-gray-700 dark:text-neutral-300 bg-white dark:bg-neutral-750 disabled:opacity-50"
          >&lt;</button>
          <span class="text-sm text-gray-700 dark:text-neutral-300">Page {{ totalPages === 0 ? 0 : currentPage }} of {{ totalPages === 0 ? 0 : totalPages }}</span>
          <button
            @click="handlePageChange(currentPage + 1)"
            :disabled="currentPage === totalPages || totalPages === 0"
            class="border border-gray-300 dark:border-neutral-700 rounded px-3 py-1 text-lg text-gray-700 dark:text-neutral-300 bg-white dark:bg-neutral-750 disabled:opacity-50"
          >&gt;</button>
        </div>
      </div>

      <!-- View Billing Modal -->
      <div v-if="showViewModal" class="fixed inset-0 flex items-center justify-center z-50 p-4 bg-black/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-neutral-800 rounded-2xl shadow-2xl p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">View Billing Details</h2>
          <div v-if="selectedBilling" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1">Billing ID</label>
                <input type="text" :value="selectedBilling.billing_id" readonly class="w-full border border-gray-300 dark:border-neutral-700 rounded px-3 py-2 bg-gray-50 dark:bg-neutral-750 text-gray-900 dark:text-white" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1">Status</label>
                <input type="text" :value="selectedBilling.status" readonly class="w-full border border-gray-300 dark:border-neutral-700 rounded px-3 py-2 bg-gray-50 dark:bg-neutral-750 text-gray-900 dark:text-white" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1">Patient Name</label>
                <input type="text" :value="selectedBilling.patient_name" readonly class="w-full border border-gray-300 dark:border-neutral-700 rounded px-3 py-2 bg-gray-50 dark:bg-neutral-750 text-gray-900 dark:text-white" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1">Billing Date</label>
                <input type="text" :value="formatDateTime(selectedBilling.billing_date, 'date')" readonly class="w-full border border-gray-300 dark:border-neutral-700 rounded px-3 py-2 bg-gray-50 dark:bg-neutral-750 text-gray-900 dark:text-white" />
              </div>
            </div>
            <div v-if="selectedBilling.appointments && selectedBilling.appointments.length > 0" class="mt-4">
              <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1">Appointments</label>
              <div v-for="(appointment, index) in selectedBilling.appointments" :key="index" class="p-3 bg-gray-50 dark:bg-neutral-750 rounded border border-gray-200 dark:border-neutral-700 mb-2">
                <span class="text-gray-900 dark:text-white">{{ formatDateTime(appointment.date, 'datetime', appointment.start_time) }} - {{ appointment.branch_name || 'N/A' }}</span>
              </div>
            </div>
            <div class="mt-6">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Treatments</h3>
              <div class="bg-gray-50 dark:bg-neutral-750 rounded-lg p-4">
                <div v-for="(treatment, index) in selectedBilling.treatments" :key="index" class="mb-3 p-3 bg-white dark:bg-neutral-800 rounded border border-gray-200 dark:border-neutral-700">
                  <div class="grid grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1">Treatment</label>
                      <input type="text" :value="treatment.treatment_name" readonly class="w-full border border-gray-300 dark:border-neutral-700 rounded px-3 py-2 bg-gray-50 dark:bg-neutral-750 text-gray-900 dark:text-white" />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1">Dentist</label>
                      <input type="text" :value="treatment.dentist_name || 'Unknown Dentist'" readonly class="w-full border border-gray-300 dark:border-neutral-700 rounded px-3 py-2 bg-gray-50 dark:bg-neutral-750 text-gray-900 dark:text-white" />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1">Description</label>
                      <input type="text" :value="treatment.description || 'N/A'" readonly class="w-full border border-gray-300 dark:border-neutral-700 rounded px-3 py-2 bg-gray-50 dark:bg-neutral-750 text-gray-900 dark:text-white" />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1">Price</label>
                      <input type="text" :value="formatCurrency(treatment.price)" readonly class="w-full border border-gray-300 dark:border-neutral-700 rounded px-3 py-2 bg-gray-50 dark:bg-neutral-750 text-gray-900 dark:text-white" />
                    </div>
                  </div>
                  <div v-if="treatment.discount_amount > 0" class="mt-2 grid grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1">Discount Amount</label>
                      <input type="text" :value="formatCurrency(treatment.discount_amount)" readonly class="w-full border border-gray-300 dark:border-neutral-700 rounded px-3 py-2 bg-gray-50 dark:bg-neutral-750 text-gray-900 dark:text-white" />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1">Discount Reason</label>
                      <input type="text" :value="treatment.discount_reason || 'N/A'" readonly class="w-full border border-gray-300 dark:border-neutral-700 rounded px-3 py-2 bg-gray-50 dark:bg-neutral-750 text-gray-900 dark:text-white" />
                    </div>
                  </div>
                  <div class="mt-2 text-right">
                    <span class="font-semibold text-lg text-gray-900 dark:text-white">{{ formatCurrency(treatment.total) }}</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="mt-4 grid grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1">Subtotal</label>
                <input type="text" :value="formatCurrency(selectedBilling.treatments.reduce((sum, t) => sum + (t.total || 0), 0))" readonly class="w-full border border-gray-300 dark:border-neutral-700 rounded px-3 py-2 bg-gray-50 dark:bg-neutral-750 font-semibold text-gray-900 dark:text-white" />
              </div>
              <div v-if="selectedBilling.discount_amount > 0">
                <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1">Discount</label>
                <input type="text" :value="`-${formatCurrency(selectedBilling.discount_amount)}`" readonly class="w-full border border-gray-300 dark:border-neutral-700 rounded px-3 py-2 bg-gray-50 dark:bg-neutral-750 text-red-600 dark:text-red-400 font-semibold" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1">Final Amount</label>
                <input type="text" :value="formatCurrency(selectedBilling.amount)" readonly class="w-full border border-gray-300 dark:border-neutral-700 rounded px-3 py-2 bg-gray-50 dark:bg-neutral-750 font-semibold text-lg text-green-600 dark:text-green-400" />
              </div>
            </div>
            <div v-if="selectedBilling.discount_reason" class="mt-4">
              <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1">Discount Reason</label>
              <textarea :value="selectedBilling.discount_reason" readonly rows="2" class="w-full border border-gray-300 dark:border-neutral-700 rounded px-3 py-2 bg-gray-50 dark:bg-neutral-750 text-gray-900 dark:text-white"></textarea>
            </div>
          </div>
          <div class="flex justify-end gap-3 mt-6">
            <button @click="closeViewModal" class="px-4 py-2 text-gray-700 dark:text-neutral-300 bg-gray-200 dark:bg-neutral-700 rounded hover:bg-gray-300 dark:hover:bg-neutral-600 transition">Close</button>
          </div>
        </div>
      </div>

      <!-- Create Billing Modal -->
      <!-- Create Billing Modal -->
<div v-if="showCreateBillingModal" class="fixed inset-0 flex items-center justify-center z-50 p-4 bg-black/60 backdrop-blur-sm">
  <div class="bg-white dark:bg-neutral-800 rounded-2xl shadow-2xl w-full max-w-6xl max-h-[90vh] overflow-hidden flex flex-col">
    
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-200 dark:border-neutral-700 flex-shrink-0 bg-gradient-to-r from-darkGreen-900 to-darkGreen-800">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold text-white">Create New Billing</h2>
          <p class="text-sm text-darkGreen-100 mt-1">Fill in the details to generate a billing statement</p>
        </div>
        <button
          @click="closeCreateBillingModal"
          class="text-white hover:text-darkGreen-100 transition-colors"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Content -->
    <div class="flex-1 overflow-y-auto">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 p-6">
        
        <!-- Left Column: Appointments & Patient Info -->
        <div class="lg:col-span-1 space-y-6">
          
          <!-- Appointment Selection -->
          <div class="bg-gray-50 dark:bg-neutral-750 rounded-xl p-4 border border-gray-200 dark:border-neutral-700">
            <label class="block text-sm font-bold text-gray-900 dark:text-white mb-3">
              <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              Select Appointments <span class="text-red-500">*</span>
            </label>
            
            <!-- Search Box -->
            <div class="relative mb-3">
              <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              <input
                v-model="appointmentSearchQuery"
                type="text"
                placeholder="Search..."
                class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border bg-white dark:bg-neutral-800 border-gray-300 dark:border-neutral-600 text-gray-900 dark:text-white placeholder-gray-400 focus:border-darkGreen-900 dark:focus:border-darkGreen-400 focus:outline-none focus:ring-1 focus:ring-darkGreen-900"
              />
            </div>

            <!-- Appointments List -->
            <div class="border border-gray-300 dark:border-neutral-600 rounded-lg max-h-64 overflow-y-auto bg-white dark:bg-neutral-800">
              <div v-if="filteredAppointments.length === 0" class="p-4 text-center text-sm text-gray-500 dark:text-neutral-400">
                No appointments available
              </div>
              <label
                v-for="appointment in filteredAppointments"
                :key="appointment.appointment_id"
                class="flex items-start gap-2 p-3 hover:bg-gray-50 dark:hover:bg-neutral-700 cursor-pointer transition-colors border-b border-gray-100 dark:border-neutral-700 last:border-b-0"
              >
                <input
                  type="checkbox"
                  :value="appointment.appointment_id"
                  v-model="billingForm.appointment_ids"
                  @change="onAppointmentSelect"
                  class="mt-1 w-4 h-4 rounded border-gray-300 text-darkGreen-900 focus:ring-darkGreen-900"
                />
                <div class="flex-1 min-w-0">
                  <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                    {{ [appointment.patient_first_name, appointment.patient_last_name].filter(Boolean).join(' ') }}
                  </div>
                  <div class="text-xs text-gray-500 dark:text-neutral-400 mt-0.5">
                    {{ formatDateTime(appointment.date, 'datetime', appointment.start_time) }}
                  </div>
                  <div class="text-xs text-darkGreen-700 dark:text-darkGreen-300 mt-1 line-clamp-1">
                    {{ appointment.services.join(', ') }}
                  </div>
                </div>
              </label>
            </div>

            <!-- Selected Summary -->
            <div v-if="billingForm.appointment_ids.length > 0" class="mt-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
              <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-blue-900 dark:text-blue-300">
                  {{ billingForm.appointment_ids.length }} selected
                </span>
                <button
                  @click="clearAppointments"
                  class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 font-medium"
                >
                  Clear
                </button>
              </div>
            </div>
          </div>

          <!-- Patient Info -->
          <div class="bg-gray-50 dark:bg-neutral-750 rounded-xl p-4 border border-gray-200 dark:border-neutral-700">
            <label class="block text-sm font-bold text-gray-900 dark:text-white mb-3">
              <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              Patient Information
            </label>
            <input
              v-model="billingForm.patient_name"
              type="text"
              readonly
              placeholder="Select appointment first"
              class="w-full px-3 py-2 text-sm rounded-lg border bg-white dark:bg-neutral-800 border-gray-300 dark:border-neutral-600 text-gray-700 dark:text-neutral-300"
            />
          </div>

        </div>

        <!-- Right Column: Treatments & Summary -->
        <div class="lg:col-span-2 space-y-6">
          
          <!-- Treatments Header -->
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
              Treatment Details
            </h3>
            <button
              v-if="canModify"
              @click="addTreatment"
              class="bg-darkGreen-900 hover:bg-darkGreen-800 text-white px-3 py-1.5 rounded-lg text-sm font-medium transition-colors flex items-center gap-1.5"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Add
            </button>
          </div>

          <!-- Treatments List - Compact Cards -->
          <div class="space-y-3">
            <div
              v-for="(treatment, index) in billingForm.treatments"
              :key="index"
              class="bg-white dark:bg-neutral-750 rounded-lg border-2 border-gray-200 dark:border-neutral-700 overflow-hidden"
            >
              <!-- Treatment Header Bar -->
              <div class="bg-gradient-to-r from-gray-100 to-gray-50 dark:from-neutral-800 dark:to-neutral-750 px-4 py-2 flex items-center justify-between border-b border-gray-200 dark:border-neutral-700">
                <span class="text-sm font-bold text-gray-700 dark:text-neutral-300">
                  Treatment {{ index + 1 }}
                </span>
                <div class="flex items-center gap-3">
                  <span class="text-lg font-bold text-darkGreen-900 dark:text-darkGreen-400">
                    {{ formatCurrency(treatment.total) }}
                  </span>
                  <button
                    v-if="billingForm.treatments.length > 1 && canModify"
                    @click="removeTreatment(index)"
                    class="text-red-600 hover:text-red-800 dark:text-red-400 transition-colors"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Treatment Content - Compact Grid -->
              <div class="p-4 grid grid-cols-2 gap-3">
                <!-- Treatment Select -->
                <div>
                  <label class="block text-xs font-semibold text-gray-700 dark:text-neutral-300 mb-1">
                    Treatment <span class="text-red-500">*</span>
                  </label>
                  <select
                    v-model="treatment.treatment_id"
                    @change="onTreatmentSelect(index)"
                    class="w-full px-3 py-2 text-sm rounded-lg border bg-white dark:bg-neutral-800 border-gray-300 dark:border-neutral-600 text-gray-900 dark:text-white focus:border-darkGreen-900 focus:ring-1 focus:ring-darkGreen-900"
                  >
                    <option value="">Select...</option>
                    <option v-for="t in treatments" :key="t.treatment_id" :value="t.treatment_id">{{ t.treatment_name }}</option>
                  </select>
                </div>

                <!-- Dentist Select -->
                <div>
                  <label class="block text-xs font-semibold text-gray-700 dark:text-neutral-300 mb-1">
                    Dentist <span class="text-red-500">*</span>
                  </label>
                  <select
                    v-model="treatment.dentist_id"
                    @change="treatment.dentist_name = getDentistName(treatment.dentist_id)"
                    class="w-full px-3 py-2 text-sm rounded-lg border bg-white dark:bg-neutral-800 border-gray-300 dark:border-neutral-600 text-gray-900 dark:text-white focus:border-darkGreen-900 focus:ring-1 focus:ring-darkGreen-900"
                  >
                    <option value="">Select...</option>
                    <option v-for="d in dentists" :key="d.dentist_id" :value="d.dentist_id">{{ getDentistName(d.dentist_id) }}</option>
                  </select>
                </div>

                <!-- Price -->
                <div>
                  <label class="block text-xs font-semibold text-gray-700 dark:text-neutral-300 mb-1">
                    Price <span class="text-red-500">*</span>
                  </label>
                  <div class="relative">
                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 text-sm">₱</span>
                    <input
                      v-model.number="treatment.price"
                      @input="updateTreatmentTotal(index)"
                      type="number"
                      min="0"
                      step="0.01"
                      placeholder="0.00"
                      class="w-full pl-7 pr-3 py-2 text-sm rounded-lg border bg-white dark:bg-neutral-800 border-gray-300 dark:border-neutral-600 text-gray-900 dark:text-white focus:border-darkGreen-900 focus:ring-1 focus:ring-darkGreen-900"
                    />
                  </div>
                </div>

                <!-- Discount -->
                <div>
                  <label class="block text-xs font-semibold text-gray-700 dark:text-neutral-300 mb-1">
                    Discount
                  </label>
                  <div class="relative">
                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 text-sm">₱</span>
                    <input
                      v-model.number="treatment.discount_amount"
                      @input="updateTreatmentTotal(index)"
                      type="number"
                      min="0"
                      step="0.01"
                      placeholder="0.00"
                      class="w-full pl-7 pr-3 py-2 text-sm rounded-lg border bg-white dark:bg-neutral-800 border-gray-300 dark:border-neutral-600 text-gray-900 dark:text-white focus:border-darkGreen-900 focus:ring-1 focus:ring-darkGreen-900"
                    />
                  </div>
                </div>

                <!-- Description - Full Width -->
                <div class="col-span-2">
                  <label class="block text-xs font-semibold text-gray-700 dark:text-neutral-300 mb-1">
                    Description / Notes
                  </label>
                  <input
                    v-model="treatment.description"
                    type="text"
                    placeholder="Optional notes about this treatment"
                    class="w-full px-3 py-2 text-sm rounded-lg border bg-white dark:bg-neutral-800 border-gray-300 dark:border-neutral-600 text-gray-900 dark:text-white focus:border-darkGreen-900 focus:ring-1 focus:ring-darkGreen-900"
                  />
                </div>

                <!-- Discount Reason - Conditional -->
                <div v-if="treatment.discount_amount > 0" class="col-span-2">
                  <label class="block text-xs font-semibold text-gray-700 dark:text-neutral-300 mb-1">
                    Discount Reason
                  </label>
                  <input
                    v-model="treatment.discount_reason"
                    type="text"
                    placeholder="Reason for discount"
                    class="w-full px-3 py-2 text-sm rounded-lg border bg-white dark:bg-neutral-800 border-gray-300 dark:border-neutral-600 text-gray-900 dark:text-white focus:border-darkGreen-900 focus:ring-1 focus:ring-darkGreen-900"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Billing Summary Card -->
          <div class="bg-gradient-to-br from-darkGreen-50 to-green-50 dark:from-neutral-800 dark:to-neutral-750 border-2 border-darkGreen-200 dark:border-neutral-700 rounded-xl p-5 shadow-lg">
            <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
              </svg>
              Payment Summary
            </h3>
            
            <div class="space-y-3">
              <!-- Subtotal -->
              <div class="flex justify-between items-center">
                <span class="text-sm font-medium text-gray-700 dark:text-neutral-300">Subtotal</span>
                <span class="text-lg font-bold text-gray-900 dark:text-white">
                  {{ formatCurrency(billingForm.treatments.reduce((sum, t) => sum + (t.total || 0), 0)) }}
                </span>
              </div>

              <!-- Additional Discount Input -->
              <div class="border-t border-darkGreen-200 dark:border-neutral-700 pt-3">
                <label class="block text-xs font-semibold text-gray-700 dark:text-neutral-300 mb-2">
                  Additional Discount
                </label>
                <div class="relative mb-2">
                  <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">₱</span>
                  <input
                    v-model.number="billingForm.discount_amount"
                    @input="calculateTotals"
                    type="number"
                    min="0"
                    step="0.01"
                    placeholder="0.00"
                    :readonly="!canModify"
                    class="w-full pl-7 pr-3 py-2 text-sm rounded-lg border bg-white dark:bg-neutral-800 border-gray-300 dark:border-neutral-600 text-gray-900 dark:text-white focus:border-darkGreen-900 focus:ring-1 focus:ring-darkGreen-900"
                  />
                </div>
                <input
                  v-model="billingForm.discount_reason"
                  type="text"
                  placeholder="Reason for additional discount (optional)"
                  :readonly="!canModify"
                  class="w-full px-3 py-2 text-sm rounded-lg border bg-white dark:bg-neutral-800 border-gray-300 dark:border-neutral-600 text-gray-900 dark:text-white focus:border-darkGreen-900 focus:ring-1 focus:ring-darkGreen-900"
                />
              </div>

              <!-- Final Amount -->
              <div class="border-t-2 border-darkGreen-300 dark:border-neutral-600 pt-3 flex justify-between items-center">
                <span class="text-base font-bold text-gray-900 dark:text-white">Total Amount</span>
                <span class="text-2xl font-bold text-green-600 dark:text-green-400">
                  {{ formatCurrency(billingForm.amount) }}
                </span>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="px-6 py-4 border-t border-gray-200 dark:border-neutral-700 flex-shrink-0 bg-gray-50 dark:bg-neutral-750">
      <div class="flex justify-between items-center">
        <div class="text-sm text-gray-600 dark:text-neutral-400">
          <span class="font-medium">{{ billingForm.treatments.length }}</span> treatment(s) added
        </div>
        <div class="flex gap-3">
          <button
            @click="closeCreateBillingModal"
            class="px-5 py-2 rounded-lg font-semibold bg-white dark:bg-neutral-700 hover:bg-gray-100 dark:hover:bg-neutral-600 text-gray-900 dark:text-white border border-gray-300 dark:border-neutral-600 transition-all"
          >
            Cancel
          </button>
          <button
            v-if="canModify"
            @click="createBilling"
            class="px-5 py-2 rounded-lg font-semibold bg-darkGreen-900 hover:bg-darkGreen-800 text-white transition-all shadow-lg"
          >
            Create Billing
          </button>
        </div>
      </div>
    </div>
  </div>
  </div>
</div>


  </AppLayout>
</template>

<style>
.bg-darkGreen-900 {
  background-color: #1e4f4f;
}

.text-darkGreen-900 {
  color: #1e4f4f;
}

.bg-darkGreen-800 {
  background-color: #2d5f5c;
}

.bg-darkGreen-50 {
  background-color: #e6f0f0;
}

.border-darkGreen-200 {
  border-color: #b3caca;
}

.text-darkGreen-400 {
  color: #4a7c7c;
}

.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

</style>