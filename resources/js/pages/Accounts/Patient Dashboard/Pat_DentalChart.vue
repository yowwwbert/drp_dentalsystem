<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

// Interface for a tooth record
interface ToothRecord {
  tooth_number: string; // FDI notation (e.g., 11, 21, 31, 41)
  condition: string; // e.g., Healthy, Cavity, Filled
  last_treatment: string; // e.g., Filling, Cleaning
  last_updated: string; // ISO date string
  notes?: string; // Optional notes
}

// Breadcrumbs for navigation
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Dental Chart', href: '/dental-chart' },
];

// Placeholder tooth records (simulating 32 adult teeth)
const toothRecords = ref<ToothRecord[]>([
  { tooth_number: '11', condition: 'Healthy', last_treatment: 'Cleaning', last_updated: '2025-09-01T10:00:00Z', notes: 'Routine checkup' },
  { tooth_number: '12', condition: 'Cavity', last_treatment: 'Filling', last_updated: '2025-08-15T14:30:00Z', notes: 'Small cavity filled' },
  { tooth_number: '21', condition: 'Filled', last_treatment: 'Filling', last_updated: '2025-07-20T09:00:00Z', notes: 'Composite filling' },
  { tooth_number: '31', condition: 'Healthy', last_treatment: 'None', last_updated: '2025-09-01T10:00:00Z' },
  // ... Add more for all 32 teeth as needed
]);

// Filtering & Search
const searchQuery = ref('');
const rowsPerPage = ref(10);
const currentPage = ref(1);

const filteredToothRecords = computed(() => {
  let filtered = toothRecords.value;
  if (searchQuery.value) {
    filtered = filtered.filter(r =>
      r.tooth_number.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      r.condition.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      r.last_treatment.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
  }
  return filtered.sort((a, b) => a.tooth_number.localeCompare(b.tooth_number));
});

const paginatedToothRecords = computed(() => {
  const start = (currentPage.value - 1) * rowsPerPage.value;
  return filteredToothRecords.value.slice(start, start + rowsPerPage.value);
});

const totalPages = computed(() => Math.ceil(filteredToothRecords.value.length / rowsPerPage.value) || 1);

function prevPage() { if (currentPage.value > 1) currentPage.value--; }
function nextPage() { if (currentPage.value < totalPages.value) currentPage.value++; }

// View Modal
const showViewModal = ref(false);
const viewToothRecord = ref<ToothRecord | null>(null);

function openViewModal(record: ToothRecord) {
  viewToothRecord.value = JSON.parse(JSON.stringify(record));
  showViewModal.value = true;
}
function closeViewModal() {
  showViewModal.value = false;
  viewToothRecord.value = null;
}

// Formatters
const formatDateTime = (dateTime: string): string =>
  dateTime
    ? new Date(dateTime).toLocaleString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
      })
    : 'N/A';

// Tooth condition color
const getConditionColor = (condition: string) => {
  switch (condition) {
    case 'Healthy': return 'bg-green-100 text-green-800';
    case 'Cavity': return 'bg-red-100 text-red-800';
    case 'Filled': return 'bg-blue-100 text-blue-800';
    default: return 'bg-gray-100 text-gray-800';
  }
};
</script>

<template>
  <Head title="My Dental Chart" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">My Dental Chart</h1>
      </div>

      <div class="bg-white rounded-lg shadow-md p-6">
        <!-- Dental Chart Visual -->
        <div class="mb-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Dental Chart</h2>
          <div class="grid grid-cols-2 gap-4">
            <!-- Upper Arch -->
            <div>
              <h3 class="text-md font-medium text-gray-700">Upper Arch</h3>
              <div class="grid grid-cols-8 gap-2 mt-2">
                <div v-for="n in [18, 17, 16, 15, 14, 13, 12, 11, 21, 22, 23, 24, 25, 26, 27, 28]" :key="n"
                     class="p-2 border rounded text-center"
                     :class="getConditionColor(toothRecords.find(r => r.tooth_number === n.toString())?.condition || 'Healthy')">
                  {{ n }}
                </div>
              </div>
            </div>
            <!-- Lower Arch -->
            <div>
              <h3 class="text-md font-medium text-gray-700">Lower Arch</h3>
              <div class="grid grid-cols-8 gap-2 mt-2">
                <div v-for="n in [48, 47, 46, 45, 44, 43, 42, 41, 31, 32, 33, 34, 35, 36, 37, 38]" :key="n"
                     class="p-2 border rounded text-center"
                     :class="getConditionColor(toothRecords.find(r => r.tooth_number === n.toString())?.condition || 'Healthy')">
                  {{ n }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tooth Records Table -->
        <div class="flex flex-col sm:flex-row gap-4 mb-6">
          <div class="flex-1">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search by tooth number or condition..."
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
            />
          </div>
          <div>
            <select
              v-model="rowsPerPage"
              @change="currentPage = 1"
              class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
            >
              <option value="5">5 per page</option>
              <option value="10">10 per page</option>
              <option value="25">25 per page</option>
            </select>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full bg-white rounded-lg shadow overflow-hidden">
            <thead>
              <tr class="bg-darkGreen-900 text-white">
                <th class="px-4 py-2 text-left">Tooth Number</th>
                <th class="px-4 py-2 text-left">Condition</th>
                <th class="px-4 py-2 text-left">Last Treatment</th>
                <th class="px-4 py-2 text-left">Last Updated</th>
                <th class="px-4 py-2 text-left">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="paginatedToothRecords.length === 0">
                <td colspan="5" class="text-center py-8 text-gray-500 text-lg">No dental records found.</td>
              </tr>
              <tr v-for="record in paginatedToothRecords" :key="record.tooth_number" class="border-b last:border-b-0 hover:bg-gray-50">
                <td class="px-4 py-2">{{ record.tooth_number }}</td>
                <td class="px-4 py-2">
                  <span :class="`px-2 py-1 rounded-full text-xs font-medium ${getConditionColor(record.condition)}`">
                    {{ record.condition }}
                  </span>
                </td>
                <td class="px-4 py-2">{{ record.last_treatment }}</td>
                <td class="px-4 py-2">{{ formatDateTime(record.last_updated) }}</td>
                <td class="px-4 py-2">
                  <button
                    @click="openViewModal(record)"
                    class="bg-gray-600 text-white px-3 py-1 rounded hover:bg-gray-700 transition text-sm"
                  >View</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mt-4 gap-4">
          <div class="text-sm text-gray-700">
            Showing {{ (currentPage - 1) * rowsPerPage + 1 }} to {{ Math.min(currentPage * rowsPerPage, filteredToothRecords.length) }} of {{ filteredToothRecords.length }} results
          </div>
          <div class="flex items-center gap-2">
            <button
              @click="prevPage"
              :disabled="currentPage === 1"
              class="border border-gray-300 rounded px-3 py-1 text-lg text-gray-700 bg-white disabled:opacity-50"
            >&lt;</button>
            <span class="text-sm text-gray-700">Page {{ currentPage }} of {{ totalPages }}</span>
            <button
              @click="nextPage"
              :disabled="currentPage === totalPages || totalPages === 0"
              class="border border-gray-300 rounded px-3 py-1 text-lg text-gray-700 bg-white disabled:opacity-50"
            >&gt;</button>
          </div>
        </div>
      </div>

      <!-- View Tooth Record Modal -->
      <div v-if="showViewModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/20 backdrop-blur-sm">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-900">Tooth Details</h2>
            <button
              @click="closeViewModal"
              class="text-gray-400 hover:text-gray-600 text-2xl font-bold"
            >&times;</button>
          </div>
          <div v-if="viewToothRecord" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Tooth Number</label>
              <p class="text-gray-900">{{ viewToothRecord.tooth_number }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Condition</label>
              <p :class="`text-sm ${getConditionColor(viewToothRecord.condition)}`">{{ viewToothRecord.condition }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Last Treatment</label>
              <p class="text-gray-900">{{ viewToothRecord.last_treatment }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Last Updated</label>
              <p class="text-gray-900">{{ formatDateTime(viewToothRecord.last_updated) }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Notes</label>
              <p class="text-gray-900">{{ viewToothRecord.notes || 'N/A' }}</p>
            </div>
            <div class="flex justify-end pt-4">
              <button
                @click="closeViewModal"
                class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 transition-colors duration-200"
              >Close</button>
            </div>
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
</style>