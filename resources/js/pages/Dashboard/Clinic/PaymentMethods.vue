<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import type { BreadcrumbItem } from '@/types';

// TypeScript interface for PaymentMethod
interface PaymentMethod {
  payment_method_id: string;
  payment_method_name: string;
  payment_method_type: string;
  description: string | null;
  is_active: boolean;
}

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Payment Methods', href: '/dashboard/payment-methods' },
];

// State management
const paymentMethods = ref<PaymentMethod[]>([]);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const loading = ref(false);
const errorMessage = ref<string | null>(null);
const paymentMethodForm = ref({
  payment_method_name: '',
  payment_method_type: '',
  description: '',
  is_active: true,
  payment_method_id: '', // Only used for edit
});

// Available payment method types (must match backend expectations)
const paymentMethodTypes = [
  { value: 'Credit Card', label: 'Credit/Debit Card' },
  { value: 'Cash', label: 'Cash' },
  { value: 'Bank Transfer', label: 'Bank Transfer' },
  { value: 'Mobile Payment', label: 'Mobile Payment' },
];

// Client-side form validation
const validateForm = () => {
  if (!paymentMethodForm.value.payment_method_name.trim()) {
    errorMessage.value = 'Payment Method Name is required';
    return false;
  }
  if (!paymentMethodForm.value.payment_method_type) {
    errorMessage.value = 'Payment Method Type is required';
    return false;
  }
  errorMessage.value = null;
  return true;
};

// Fetch payment methods
const fetchPaymentMethods = async () => {
  try {
    loading.value = true;
    const response = await axios.get('/api/payment-methods');
    paymentMethods.value = response.data.data;
  } catch (error) {
    console.error('Error fetching payment methods:', error);
    errorMessage.value = error.response?.data?.error || 'Failed to fetch payment methods';
  } finally {
    loading.value = false;
  }
};

// Create payment method
const createPaymentMethod = async () => {
  if (!validateForm()) return;

  try {
    loading.value = true;
    errorMessage.value = null;
    await axios.post('/api/payment-methods', paymentMethodForm.value);
    await fetchPaymentMethods();
    closeCreateModal();
  } catch (error) {
    console.error('Error creating payment method:', error);
    errorMessage.value = error.response?.data?.error || 'Failed to create payment method';
  } finally {
    loading.value = false;
  }
};

// Edit payment method
const editPaymentMethod = (method: PaymentMethod) => {
  paymentMethodForm.value = {
    payment_method_id: method.payment_method_id,
    payment_method_name: method.payment_method_name,
    payment_method_type: method.payment_method_type,
    description: method.description || '',
    is_active: method.is_active,
  };
  errorMessage.value = null;
  showEditModal.value = true;
};

// Update payment method
const updatePaymentMethod = async () => {
  if (!validateForm()) return;

  try {
    loading.value = true;
    errorMessage.value = null;
    await axios.put(`/api/payment-methods/${paymentMethodForm.value.payment_method_id}`, paymentMethodForm.value);
    await fetchPaymentMethods();
    closeEditModal();
  } catch (error) {
    console.error('Error updating payment method:', error);
    errorMessage.value = error.response?.data?.error || 'Failed to update payment method';
  } finally {
    loading.value = false;
  }
};

// Modal controls
const openCreateModal = () => {
  paymentMethodForm.value = {
    payment_method_name: '',
    payment_method_type: '',
    description: '',
    is_active: true,
    payment_method_id: '',
  };
  errorMessage.value = null;
  showCreateModal.value = true;
};

const closeCreateModal = () => {
  showCreateModal.value = false;
  errorMessage.value = null;
};

const closeEditModal = () => {
  showEditModal.value = false;
  errorMessage.value = null;
};

// Initialize data
onMounted(() => {
  fetchPaymentMethods();
});
</script>

<template>
  <Head title="Payment Methods Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-4 rounded-xl p-4 bg-gray-50 min-h-screen relative">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-4">
        <h1 class="text-3xl font-bold text-gray-900">Payment Methods Management</h1>
        <button
          @click="openCreateModal"
          class="bg-darkGreen-900 text-white px-6 py-2 rounded font-semibold shadow hover:bg-darkGreen-800 transition"
          :disabled="loading"
        >
          Add Payment Method
        </button>
      </div>

      <!-- Error Message -->
      <div v-if="errorMessage" class="bg-red-100 text-red-700 p-4 rounded-lg mb-4">
        {{ errorMessage }}
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center py-8 text-gray-500 text-lg">
        Loading payment methods...
      </div>

      <!-- Payment Methods List -->
      <div v-else class="bg-white rounded-xl shadow p-4">
        <div v-if="paymentMethods.length === 0" class="text-center py-8 text-gray-500 text-lg">
          No payment methods found.
        </div>
        <div v-else class="grid gap-4">
          <div v-for="method in paymentMethods" :key="method.payment_method_id" class="border rounded-lg p-4 flex justify-between items-center hover:bg-gray-50">
            <div>
              <h3 class="text-lg font-semibold text-gray-900">{{ method.payment_method_name }}</h3>
              <p class="text-sm text-gray-600">ID: {{ method.payment_method_id }}</p>
              <p class="text-sm text-gray-600">Type: {{ method.payment_method_type }}</p>
              <p class="text-sm text-gray-600">Description: {{ method.description || 'N/A' }}</p>
              <p class="text-sm" :class="method.is_active ? 'text-green-600' : 'text-red-600'">
                Status: {{ method.is_active ? 'Active' : 'Inactive' }}
              </p>
            </div>
            <button
              @click="editPaymentMethod(method)"
              class="bg-darkGreen-900 text-white px-4 py-2 rounded hover:bg-darkGreen-800 transition text-sm"
              :disabled="loading"
            >
              Edit
            </button>
          </div>
        </div>
      </div>

      <!-- Create Payment Method Modal -->
      <div v-if="showCreateModal" class="absolute inset-0 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
          <h2 class="text-xl font-bold text-gray-900 mb-4">Add New Payment Method</h2>
          <div class="space-y-4">
            <div v-if="errorMessage" class="bg-red-100 text-red-700 p-2 rounded text-sm">
              {{ errorMessage }}
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
              <input
                v-model="paymentMethodForm.payment_method_name"
                type="text"
                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                placeholder="e.g., Credit Card"
                :disabled="loading"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
              <select
                v-model="paymentMethodForm.payment_method_type"
                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                :disabled="loading"
              >
                <option value="">Select a type</option>
                <option v-for="type in paymentMethodTypes" :key="type.value" :value="type.value">
                  {{ type.label }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
              <textarea
                v-model="paymentMethodForm.description"
                rows="3"
                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                placeholder="Optional description"
                :disabled="loading"
              ></textarea>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Active</label>
              <input
                v-model="paymentMethodForm.is_active"
                type="checkbox"
                class="h-5 w-5 text-darkGreen-900 focus:ring-darkGreen-900 border-gray-300 rounded"
                :disabled="loading"
              />
            </div>
          </div>
          <div class="flex justify-end gap-3 mt-6">
            <button
              @click="closeCreateModal"
              class="px-4 py-2 text-gray-700 bg-gray-200 rounded hover:bg-gray-300 transition"
              :disabled="loading"
            >
              Cancel
            </button>
            <button
              @click="createPaymentMethod"
              class="px-4 py-2 text-white bg-darkGreen-900 rounded hover:bg-darkGreen-800 transition"
              :disabled="loading"
            >
              {{ loading ? 'Creating...' : 'Create' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Edit Payment Method Modal -->
      <div v-if="showEditModal" class="absolute inset-0 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
          <h2 class="text-xl font-bold text-gray-900 mb-4">Edit Payment Method</h2>
          <div class="space-y-4">
            <div v-if="errorMessage" class="bg-red-100 text-red-700 p-2 rounded text-sm">
              {{ errorMessage }}
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method ID</label>
              <input
                v-model="paymentMethodForm.payment_method_id"
                type="text"
                readonly
                class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
              <input
                v-model="paymentMethodForm.payment_method_name"
                type="text"
                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                placeholder="e.g., Credit Card"
                :disabled="loading"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
              <select
                v-model="paymentMethodForm.payment_method_type"
                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                :disabled="loading"
              >
                <option value="">Select a type</option>
                <option v-for="type in paymentMethodTypes" :key="type.value" :value="type.value">
                  {{ type.label }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
              <textarea
                v-model="paymentMethodForm.description"
                rows="3"
                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                placeholder="Optional description"
                :disabled="loading"
              ></textarea>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Active</label>
              <input
                v-model="paymentMethodForm.is_active"
                type="checkbox"
                class="h-5 w-5 text-darkGreen-900 focus:ring-darkGreen-900 border-gray-300 rounded"
                :disabled="loading"
              />
            </div>
          </div>
          <div class="flex justify-end gap-3 mt-6">
            <button
              @click="closeEditModal"
              class="px-4 py-2 text-gray-700 bg-gray-200 rounded hover:bg-gray-300 transition"
              :disabled="loading"
            >
              Cancel
            </button>
            <button
              @click="updatePaymentMethod"
              class="px-4 py-2 text-white bg-darkGreen-900 rounded hover:bg-darkGreen-800 transition"
              :disabled="loading"
            >
              {{ loading ? 'Saving...' : 'Save Changes' }}
            </button>
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