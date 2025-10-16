<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

interface Patient {
  patient_id: string;
  first_name: string;
  last_name: string;
  email_address: string;
  phone_number: string;
  age: number | null;
  sex: string;
  created_at: string;
  balance: number;
}

const patients = ref<Patient[]>([]);
const isLoading = ref(true);
const error = ref<string | null>(null);
const isNavigating = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Patient Records', href: '/dashboard/patients' },
];

const searchQuery = ref('');
const rowsPerPage = ref(10);
const currentPage = ref(1);

const filteredPatients = computed(() => {
  if (!searchQuery.value) return patients.value;
  const query = searchQuery.value.toLowerCase();
  return patients.value.filter(p =>
    (p.first_name ?? '').toLowerCase().includes(query) ||
    (p.last_name ?? '').toLowerCase().includes(query) ||
    (p.email_address ?? '').toLowerCase().includes(query) ||
    (p.phone_number ?? '').toLowerCase().includes(query)
  );
});

const paginatedPatients = computed(() => {
  const start = (currentPage.value - 1) * rowsPerPage.value;
  return filteredPatients.value.slice(start, start + rowsPerPage.value);
});

const totalPages = computed(() =>
  Math.ceil(filteredPatients.value.length / rowsPerPage.value) || 1
);

function prevPage() {
  if (currentPage.value > 1) currentPage.value--;
}

function nextPage() {
  if (currentPage.value < totalPages.value) currentPage.value++;
}

function viewDentalChart(patientId: string) {
  isNavigating.value = true;
  const patient = patients.value.find(p => p.patient_id === patientId);
  
  // Use POST to hide patient_id from URL
  router.post('/dentalChart', { 
    patient_id: patientId,
    first_name: patient?.first_name || '',
    last_name: patient?.last_name || ''
  });
}

const formatDate = (date: string): string =>
  date
    ? new Date(date).toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
      })
    : 'N/A';

onMounted(async () => {
  try {
    const response = await axios.get('/dashboard/patients');
    patients.value = response.data;
  } catch (err) {
    error.value = 'Failed to load patient records. Please try again later.';
    console.error('Error fetching patients:', err);
  } finally {
    isLoading.value = false;
  }
});
</script>

<template>
  <Head title="Patient Records" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">Patient Records</h1>
      </div>

      <div v-if="isLoading" class="text-center py-4 text-gray-500">
        Loading patients...
      </div>

      <div v-else-if="error" class="text-center py-4 text-red-500">
        {{ error }}
      </div>

      <div v-else class="bg-white rounded-lg shadow-md p-6">
        <div class="flex flex-col sm:flex-row gap-4 mb-6">
          <div class="flex-1">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search by name, email, or phone..."
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
            />
          </div>
          <div class="flex gap-2">
            <select
              v-model="rowsPerPage"
              @change="currentPage = 1"
              class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
            >
              <option :value="5">5 per page</option>
              <option :value="10">10 per page</option>
              <option :value="25">25 per page</option>
              <option :value="50">50 per page</option>
            </select>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full bg-white rounded-lg shadow overflow-hidden">
            <thead>
              <tr class="bg-[#1e4f4f] text-white">
                <th class="px-4 py-2 text-left">Name</th>
                <th class="px-4 py-2 text-left">Email Address</th>
                <th class="px-4 py-2 text-left">Phone Number</th>
                <th class="px-4 py-2 text-left">Last Visit</th>
                <th class="px-4 py-2 text-left">Balance</th>
                <th class="px-4 py-2 text-left">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="paginatedPatients.length === 0">
                <td colspan="6" class="text-center py-8 text-gray-500 text-lg">
                  No patients found.
                </td>
              </tr>
              <tr 
                v-for="patient in paginatedPatients" 
                :key="patient.patient_id" 
                class="border-b last:border-b-0 hover:bg-gray-50"
              >
                <td class="px-4 py-2">
                  <div class="font-medium">
                    {{ patient.last_name || 'N/A' }}, {{ patient.first_name || 'N/A' }}
                  </div>
                  <div v-if="patient.age || patient.sex" class="text-sm text-gray-500">
                    {{ patient.age ? `${patient.age} yrs` : '' }}{{ patient.age && patient.sex ? ' • ' : '' }}{{ patient.sex || '' }}
                  </div>
                </td>
                <td class="px-4 py-2 text-gray-700">
                  {{ patient.email_address || 'N/A' }}
                </td>
                <td class="px-4 py-2 text-gray-700">
                  {{ patient.phone_number || 'N/A' }}
                </td>
                <td class="px-4 py-2 text-gray-700">
                  {{ formatDate(patient.created_at) }}
                </td>
                <td class="px-4 py-2">
                  <span :class="patient.balance > 0 ? 'text-red-600 font-semibold' : 'text-green-600'">
                    ₱{{ patient.balance || 0 }}
                  </span>
                </td>
                <td class="px-4 py-2">
                  <button 
                    @click="viewDentalChart(patient.patient_id)"
                    :disabled="isNavigating"
                    class="bg-darkGreen-900 text-white px-4 py-1 rounded hover:bg-darkGreen-800 transition text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    {{ isNavigating ? 'Loading...' : 'View Chart' }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex flex-col sm:flex-row sm:justify-end sm:items-center mt-4 gap-4">
          <div class="flex items-center gap-2">
            <button
              @click="prevPage"
              :disabled="currentPage === 1"
              class="border border-gray-300 rounded px-3 py-1 text-lg text-gray-700 bg-white disabled:opacity-50"
            >
              &lt;
            </button>
            <span class="text-sm text-gray-700">Page {{ currentPage }} of {{ totalPages }}</span>
            <button
              @click="nextPage"
              :disabled="currentPage === totalPages || totalPages === 0"
              class="border border-gray-300 rounded px-3 py-1 text-lg text-gray-700 bg-white disabled:opacity-50"
            >
              &gt;
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
  background-color: #2d5f5c;
}

.ring-darkGreen-900 {
  --tw-ring-color: #1e4f4f;
}
</style>