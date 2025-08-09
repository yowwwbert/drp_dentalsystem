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
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">Patient Records</h1>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-end items-center mb-4">
                  <input v-model="searchQuery" type="text" placeholder="Search by name..." class="border rounded px-3 py-1 w-64" />
                </div>
                <div class="overflow-x-auto">
                  <table class="min-w-full bg-white rounded-lg shadow overflow-hidden">
                    <thead>
                      <tr class="bg-teal-600 text-white rounded-t-lg">
                        <th class="px-4 py-2 text-left">ID</th>
                        <th class="px-4 py-2 text-left">Name</th>
                        <th class="px-4 py-2 text-left">Email</th>
                        <th class="px-4 py-2 text-left">Phone</th>
                        <th class="px-4 py-2 text-left">Age</th>
                        <th class="px-4 py-2 text-left">Sex</th>
                        <th class="px-4 py-2 text-left">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="patient in paginatedPatients" :key="patient.id" class="border-b hover:bg-gray-50">
                        <td class="px-4 py-2">{{ patient.id }}</td>
                        <td class="px-4 py-2">{{ patient.name }}</td>
                        <td class="px-4 py-2">{{ patient.email }}</td>
                        <td class="px-4 py-2">{{ patient.phone }}</td>
                        <td class="px-4 py-2">{{ patient.age }}</td>
                        <td class="px-4 py-2">{{ patient.sex }}</td>
                        <td class="px-4 py-2">
                          <button class="bg-green-900 text-white px-4 py-1 rounded hover:bg-green-700">View</button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div class="flex items-center justify-between mt-4">
                  <div>
                    <label class="mr-2">Rows per page:</label>
                    <select v-model="rowsPerPage" class="border rounded px-2 py-1">
                      <option :value="10">10</option>
                      <option :value="25">25</option>
                      <option :value="50">50</option>
                    </select>
                  </div>
                  <div class="flex items-center gap-2">
                    <button @click="prevPage" :disabled="currentPage === 1" class="px-2 py-1 rounded border disabled:opacity-50">&lt;</button>
                    <span>Page {{ currentPage }} of {{ totalPages }}</span>
                    <button @click="nextPage" :disabled="currentPage === totalPages" class="px-2 py-1 rounded border disabled:opacity-50">&gt;</button>
                  </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template> 