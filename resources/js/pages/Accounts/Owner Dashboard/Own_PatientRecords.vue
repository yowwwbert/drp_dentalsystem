<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
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

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Patient Records', href: '/dashboard/patients' },
];

const searchQuery = ref('');
const rowsPerPage = ref(10);
const currentPage = ref(1);

const filteredPatients = computed(() => {
  if (!searchQuery.value) return patients.value;
  return patients.value.filter(p =>
    (p.first_name ?? '').toLowerCase().includes(searchQuery.value.toLowerCase())
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
        <div class="flex justify-end items-center mb-2 rounded-md">
          <input v-model="searchQuery" type="text" placeholder="Search patient"
            class="border-1 border-[#1E4F4F] rounded-xl px-3 py-1 w-64 focus:outline-none focus:ring-2 focus:ring-green-900" />
        </div>
      </div>

      <div v-if="isLoading" class="text-center py-4 text-gray-500">
        Loading patients...
      </div>

      <div v-else-if="error" class="text-center py-4 text-red-500">
        {{ error }}
      </div>

      <div v-else class="bg-white rounded-lg shadow-md p-6">


        <div class="overflow-x-auto">
          <table class="min-w-full bg-white shadow overflow-hidden">
            <thead>
              <tr class="bg-[#1E4F4F] text-white rounded-t-lg">
                <th class="px-4 py-2 text-left">Name</th>
                <th class="px-4 py-2 text-left">Email Address</th>
                <th class="px-4 py-2 text-left">Phone Number</th>
                <th class="px-4 py-2 text-left">Last Visit</th>
                <th class="px-4 py-2 text-left">Balance</th>
                <th class="px-2 py-2"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="patient in paginatedPatients" :key="patient.patient_id" class="border-b hover:bg-gray-50">
                <td class="px-4 py-2">{{ patient.last_name || 'N/A' }}, {{ patient.first_name || 'N/A' }}</td>
                <td class="px-4 py-2">{{ patient.email_address || 'N/A' }}</td>
                <td class="px-4 py-2">{{ patient.phone_number || 'N/A' }}</td>
                <td class="px-4 py-2">
                  {{ patient.created_at ? new Date(patient.created_at).toLocaleDateString('en-US', {
                    month: 'long',
                    day: 'numeric',
                  year: 'numeric'
                  }) : 'N/A' }}
                </td>
                <td class="px-4 py-2">₱ {{ patient.balance || 0 }} </td>
                <td class=" py-2">
                  <button class="bg-[#3E7F7B] text-white px-8 py-2 rounded-lg">View</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="filteredPatients.length === 0" class="text-center py-4 text-gray-500">
          No patients found.
        </div>

        <div class="flex items-center justify-between mt-4">
          <div>
            <label class="mr-2 text-gray-700">Rows per page:</label>
            <select v-model="rowsPerPage"
              class="border rounded px-2 py-1 focus:outline-none focus:ring-2 focus:ring-green-900">
              <option :value="10">10</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
            </select>
          </div>

          <div class="flex items-center gap-2">
            <button @click="prevPage" :disabled="currentPage === 1"
              class="px-2 py-1 rounded border disabled:opacity-50 hover:bg-gray-100 transition-colors">
              &lt;
            </button>
            <span class="text-gray-700">
              Page {{ currentPage }} of {{ totalPages }}
            </span>
            <button @click="nextPage" :disabled="currentPage === totalPages"
              class="px-2 py-1 rounded border disabled:opacity-50 hover:bg-gray-100 transition-colors">
              &gt;
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>