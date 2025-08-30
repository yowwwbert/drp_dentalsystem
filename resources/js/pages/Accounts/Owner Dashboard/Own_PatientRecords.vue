<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Patient Records',
        href: '/records/patients',
    },
];

// Mock patient data (replace with real data source as needed)
const patients = ref([
  { id: 'PAT-0001', name: 'Atencio, Robert', email: 'atencio.robertace@gmail.com', phone: '+639278087196', age: 21, sex: 'Male' },
  { id: 'PAT-0002', name: 'Arao, Gabriel Jophel', email: 'arao.gabriel@gmail.com', phone: '+639927688722', age: 21, sex: 'Male' },
  { id: 'PAT-0003', name: 'Atencio, Casandra', email: 'casandraysabel@yahoo.com', phone: '+639927688722', age: 30, sex: 'Female' }
]);

const searchQuery = ref('');
const rowsPerPage = ref(10);
const currentPage = ref(1);

const filteredPatients = computed(() => {
  if (!searchQuery.value) return patients.value;
  return patients.value.filter(p =>
    p.name.toLowerCase().includes(searchQuery.value.toLowerCase())
  );
});

const paginatedPatients = computed(() => {
  const start = (currentPage.value - 1) * rowsPerPage.value;
  return filteredPatients.value.slice(start, start + rowsPerPage.value);
});

const totalPages = computed(() => Math.ceil(filteredPatients.value.length / rowsPerPage.value));
function prevPage() { if (currentPage.value > 1) currentPage.value--; }
function nextPage() { if (currentPage.value < totalPages.value) currentPage.value++; }
</script>

<template>
    <Head title="Patient Records" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 rounded-xl p-4 bg-gray-50 min-h-screen relative">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-2 gap-4">
                <h1 class="text-3xl font-bold text-gray-900">Patient Records</h1>
                <div class="flex flex-1 justify-end items-center gap-2">
                  <input 
                  v-model="searchQuery" type="text" 
                  placeholder="Search by name..." class="border border-gray-300 rounded px-4 py-2 w-64 focus:outline-none focus:ring-2 focus:ring-teal-900" />
                </div>
              </div>
                <div class="bg-white rounded-xl shadow p-0 overflow-x-auto">
                  <table class="w-full min-w-[900px] border-separate border-spacing-0">
                    <thead>
                      <tr class="bg-teal-900 text-white">
                        <th class="px-3 py-4 text-left font-semibold">ID</th>
                        <th class="px-3 py-4 text-left font-semibold">Name</th>
                        <th class="px-3 py-4 font-semibold text-left">Email</th>
                        <th class="px-3 py-4 font-semibold text-left">Phone</th>
                        <th class="px-3 py-4 font-semibold text-left">Age</th>
                        <th class="px-3 py-4 font-semibold text-left">Sex</th>
                        <th class="px-3 py-4 font-semibold text-left">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="patient in paginatedPatients" :key="patient.id" class="border-b last:border-b-0 hover:bg-gray-50">
                        <td class="px-4 py-3">{{ patient.id }}</td>
                        <td class="px-4 py-3">{{ patient.name }}</td>
                        <td class="px-4 py-3">{{ patient.email }}</td>
                        <td class="px-4 py-3">{{ patient.phone }}</td>
                        <td class="px-4 py-3">{{ patient.age }}</td>
                        <td class="px-4 py-3">{{ patient.sex }}</td>
                        <td class="px-4 py-3">
                          <button class="bg-teal-900 text-white px-4 py-1 rounded hover:bg-teal-700 transition">View</button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mt-4 gap-4">
                  <div>
                    <label class="text-sm text-gray-700 mr-2">Rows per page:</label>
                    <select v-model="rowsPerPage" class="border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-teal-900">
                      <option :value="10">10</option>
                      <option :value="25">25</option>
                      <option :value="50">50</option>
                    </select>
                  </div>
                  <div class="flex items-center gap-2">
                    <button @click="prevPage" :disabled="currentPage === 1" class="border border-gray-300 rounded px-3 py-1 text-lg text-gray-700 bg-white disabled:opacity-50">&lt;</button>
                    <span>Page {{ currentPage }} of {{ totalPages }}</span>
                    <button @click="nextPage" :disabled="currentPage === totalPages" class="border border-gray-300 rounded px-3 py-1 text-lg text-gray-700 bg-white disabled:opacity-50">&gt;</button>
                  </div>
                </div>
        </div>
    </AppLayout>
</template> 