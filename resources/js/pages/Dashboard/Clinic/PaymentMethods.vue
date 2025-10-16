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
  <Head title="Payment Methods" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="min-h-screen bg-gray-50 dark:bg-neutral-900 transition-colors duration-300 rounded-xl mt-2 p-4">
      <div class="px-4 py-4 mx-auto">
        <!-- Header Section -->
        <div class="mb-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4" :class="{ 'blur-sm': showCreateModal || showEditModal }">
          <div>
            <h1 class="text-4xl font-bold mb-2 text-gray-900 dark:text-white">
              Payment Methods
            </h1>
            <p class="text-lg text-gray-600 dark:text-neutral-400">
              Manage payment options and configurations
            </p>
          </div>
          <button
            @click="openCreateModal"
            class="bg-darkGreen-900 hover:bg-darkGreen-800 text-white px-6 py-3.5 rounded-xl font-semibold shadow-lg transition-all duration-200 flex items-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add Payment Method
          </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading && paymentMethods.length === 0" class="text-center py-16 text-gray-600 dark:text-neutral-300">
          <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-t-darkGreen-900 border-r-darkGreen-900 border-b-transparent border-l-transparent"></div>
          <p class="mt-4 text-lg">Loading payment methods...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="errorMessage && paymentMethods.length === 0" class="text-center py-16 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-300 rounded-2xl">
          <p class="text-lg">{{ errorMessage }}</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="paymentMethods.length === 0" class="text-center py-20 rounded-2xl bg-white dark:bg-neutral-800 text-gray-500 dark:text-neutral-400" :class="{ 'blur-sm': showCreateModal || showEditModal }">
          <svg class="w-16 h-16 mx-auto mb-4 text-gray-400 dark:text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
          </svg>
          <p class="text-xl mb-2">No payment methods found</p>
          <p class="text-sm">Create your first payment method to get started</p>
        </div>

        <!-- Cards Grid -->
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" :class="{ 'blur-sm': showCreateModal || showEditModal }">
          <div
            v-for="method in paymentMethods"
            :key="method.payment_method_id"
            class="group relative rounded-2xl p-6 shadow-lg border bg-white dark:bg-neutral-800 border-gray-100 dark:border-neutral-700 transition-all duration-300 hover:scale-[1.02] hover:shadow-xl"
          >
            <!-- Payment Method Name (Primary) -->
            <div class="mb-4">
              <h3 class="font-bold text-2xl text-gray-900 dark:text-white line-clamp-2 mb-3">
                {{ method.payment_method_name }}
              </h3>
              
              <!-- Payment Method Details (Secondary) -->
              <div class="space-y-2">
                <div class="flex items-center gap-2">
                  <span class="text-xs font-semibold text-gray-500 dark:text-neutral-500">Type:</span>
                  <span class="text-sm font-medium text-gray-700 dark:text-neutral-300">
                    {{ method.payment_method_type }}
                  </span>
                </div>
                
                <div v-if="method.description" class="text-sm text-gray-600 dark:text-neutral-400 line-clamp-2">
                  {{ method.description }}
                </div>
                
                <div class="flex items-center gap-2">
                  <span 
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    :class="method.is_active 
                      ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' 
                      : 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300'"
                  >
                    {{ method.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="pt-4 border-t border-gray-100 dark:border-neutral-700">
              <button
                @click="editPaymentMethod(method)"
                class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg font-medium bg-darkGreen-900 hover:bg-darkGreen-800 text-white transition-all duration-200"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Edit
              </button>
            </div>
          </div>
        </div>

        <!-- Create Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
          <div class="w-full max-w-md rounded-2xl shadow-2xl bg-white dark:bg-neutral-800">
            <div class="px-6 py-5 border-b border-gray-200 dark:border-neutral-700">
              <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Create Payment Method</h2>
                <button
                  @click="closeCreateModal"
                  class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                >
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
            </div>

            <div class="p-6 space-y-5">
              <div>
                <label class="block text-sm font-semibold mb-2 text-gray-700 dark:text-neutral-300">
                  Payment Method Name <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="paymentMethodForm.payment_method_name"
                  type="text"
                  placeholder="e.g., Credit Card"
                  class="w-full px-4 py-3 rounded-xl border bg-white dark:bg-neutral-750 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-neutral-500 focus:border-darkGreen-900 dark:focus:border-darkGreen-400 focus:outline-none focus:ring-2 focus:ring-darkGreen-900/30 dark:focus:ring-darkGreen-400/30 transition-all duration-200"
                />
              </div>

              <div>
                <label class="block text-sm font-semibold mb-2 text-gray-700 dark:text-neutral-300">
                  Type <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="paymentMethodForm.payment_method_type"
                  class="w-full px-4 py-3 rounded-xl border bg-white dark:bg-neutral-750 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white focus:border-darkGreen-900 dark:focus:border-darkGreen-400 focus:outline-none focus:ring-2 focus:ring-darkGreen-900/30 dark:focus:ring-darkGreen-400/30 transition-all duration-200"
                >
                  <option value="">Select a type</option>
                  <option v-for="type in paymentMethodTypes" :key="type.value" :value="type.value">
                    {{ type.label }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-semibold mb-2 text-gray-700 dark:text-neutral-300">
                  Description
                </label>
                <textarea
                  v-model="paymentMethodForm.description"
                  rows="3"
                  placeholder="Optional description"
                  class="w-full px-4 py-3 rounded-xl border bg-white dark:bg-neutral-750 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-neutral-500 focus:border-darkGreen-900 dark:focus:border-darkGreen-400 focus:outline-none focus:ring-2 focus:ring-darkGreen-900/30 dark:focus:ring-darkGreen-400/30 transition-all duration-200"
                ></textarea>
              </div>

              <div class="flex items-center gap-3">
                <input
                  v-model="paymentMethodForm.is_active"
                  type="checkbox"
                  id="create-is-active"
                  class="h-5 w-5 text-darkGreen-900 focus:ring-darkGreen-900 border-gray-300 dark:border-neutral-600 rounded cursor-pointer"
                />
                <label for="create-is-active" class="text-sm font-semibold text-gray-700 dark:text-neutral-300 cursor-pointer">
                  Active
                </label>
              </div>

              <div v-if="errorMessage" class="p-3 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-300 text-sm">
                {{ errorMessage }}
              </div>
            </div>

            <div class="px-6 py-4 border-t border-gray-200 dark:border-neutral-700">
              <div class="flex justify-end gap-3">
                <button
                  @click="closeCreateModal"
                  class="px-6 py-2.5 rounded-xl font-semibold bg-gray-100 dark:bg-neutral-700 hover:bg-gray-200 dark:hover:bg-neutral-600 text-gray-900 dark:text-white transition-all duration-200"
                >
                  Cancel
                </button>
                <button
                  @click="createPaymentMethod"
                  :disabled="loading"
                  class="px-6 py-2.5 rounded-xl font-semibold bg-darkGreen-900 hover:bg-darkGreen-800 text-white transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{ loading ? 'Creating...' : 'Create' }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Edit Modal -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
          <div class="w-full max-w-md rounded-2xl shadow-2xl bg-white dark:bg-neutral-800">
            <div class="px-6 py-5 border-b border-gray-200 dark:border-neutral-700">
              <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Payment Method</h2>
                <button
                  @click="closeEditModal"
                  class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                >
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
            </div>

            <div class="p-6 space-y-5">
              <div>
                <label class="block text-sm font-semibold mb-2 text-gray-700 dark:text-neutral-300">
                  Payment Method ID
                </label>
                <input
                  v-model="paymentMethodForm.payment_method_id"
                  type="text"
                  readonly
                  class="w-full px-4 py-3 rounded-xl border bg-gray-50 dark:bg-neutral-900 border-gray-300 dark:border-neutral-700 text-gray-600 dark:text-neutral-400"
                />
              </div>

              <div>
                <label class="block text-sm font-semibold mb-2 text-gray-700 dark:text-neutral-300">
                  Payment Method Name <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="paymentMethodForm.payment_method_name"
                  type="text"
                  placeholder="e.g., Credit Card"
                  class="w-full px-4 py-3 rounded-xl border bg-white dark:bg-neutral-750 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-neutral-500 focus:border-darkGreen-900 dark:focus:border-darkGreen-400 focus:outline-none focus:ring-2 focus:ring-darkGreen-900/30 dark:focus:ring-darkGreen-400/30 transition-all duration-200"
                />
              </div>

              <div>
                <label class="block text-sm font-semibold mb-2 text-gray-700 dark:text-neutral-300">
                  Type <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="paymentMethodForm.payment_method_type"
                  class="w-full px-4 py-3 rounded-xl border bg-white dark:bg-neutral-750 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white focus:border-darkGreen-900 dark:focus:border-darkGreen-400 focus:outline-none focus:ring-2 focus:ring-darkGreen-900/30 dark:focus:ring-darkGreen-400/30 transition-all duration-200"
                >
                  <option value="">Select a type</option>
                  <option v-for="type in paymentMethodTypes" :key="type.value" :value="type.value">
                    {{ type.label }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-semibold mb-2 text-gray-700 dark:text-neutral-300">
                  Description
                </label>
                <textarea
                  v-model="paymentMethodForm.description"
                  rows="3"
                  placeholder="Optional description"
                  class="w-full px-4 py-3 rounded-xl border bg-white dark:bg-neutral-750 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-neutral-500 focus:border-darkGreen-900 dark:focus:border-darkGreen-400 focus:outline-none focus:ring-2 focus:ring-darkGreen-900/30 dark:focus:ring-darkGreen-400/30 transition-all duration-200"
                ></textarea>
              </div>

              <div class="flex items-center gap-3">
                <input
                  v-model="paymentMethodForm.is_active"
                  type="checkbox"
                  id="edit-is-active"
                  class="h-5 w-5 text-darkGreen-900 focus:ring-darkGreen-900 border-gray-300 dark:border-neutral-600 rounded cursor-pointer"
                />
                <label for="edit-is-active" class="text-sm font-semibold text-gray-700 dark:text-neutral-300 cursor-pointer">
                  Active
                </label>
              </div>

              <div v-if="errorMessage" class="p-3 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-300 text-sm">
                {{ errorMessage }}
              </div>
            </div>

            <div class="px-6 py-4 border-t border-gray-200 dark:border-neutral-700">
              <div class="flex justify-end gap-3">
                <button
                  @click="closeEditModal"
                  class="px-6 py-2.5 rounded-xl font-semibold bg-gray-100 dark:bg-neutral-700 hover:bg-gray-200 dark:hover:bg-neutral-600 text-gray-900 dark:text-white transition-all duration-200"
                >
                  Cancel
                </button>
                <button
                  @click="updatePaymentMethod"
                  :disabled="loading"
                  class="px-6 py-2.5 rounded-xl font-semibold bg-darkGreen-900 hover:bg-darkGreen-800 text-white transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{ loading ? 'Updating...' : 'Update' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.bg-darkGreen-900 {
  background-color: #1e4f4f;
}

.text-darkGreen-900 {
  color: #1e4f4f;
}

.bg-darkGreen-800 {
  background-color: #2d5f5c;
}

.bg-neutral-750 {
  background-color: #2a2a2a;
}
</style>