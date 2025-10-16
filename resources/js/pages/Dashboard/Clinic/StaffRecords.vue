<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

interface Staff {
  staff_id: string;
  first_name: string;
  last_name: string;
  email: string;
  phone_number: string;
  position: string;
  branches: Array<{ branch_id: string; name: string }>;
  created_at: string;
  updated_at: string;
}

interface Branch {
  branch_id: string;
  name: string;
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Staff Records', href: '/records/staff' },
];

const searchQuery = ref('');
const selectedBranch = ref('All Branches');
const currentPage = ref(1);
const rowsPerPage = ref(10);
const isLoading = ref(true);
const error = ref<string | null>(null);

const staffList = ref<Staff[]>([]);
const branches = ref<Branch[]>([]);

// Modal states
const showViewModal = ref(false);
const showEditModal = ref(false);
const selectedStaff = ref<Staff | null>(null);

// Form data for edit modal
const editForm = ref({
  staff_id: '',
  firstName: '',
  lastName: '',
  email: '',
  phoneNumber: '',
  position: '',
  branch_id: ''
});

const isEditMode = computed(() => !!editForm.value.staff_id);

onMounted(async () => {
  await fetchStaffRecords();
});

const fetchStaffRecords = async () => {
  try {
    isLoading.value = true;
    const params: Record<string, string> = {};
    
    if (selectedBranch.value !== 'All Branches') {
      const branch = branches.value.find(b => b.name === selectedBranch.value);
      if (branch) {
        params.branch_id = branch.branch_id;
      }
    }

    const response = await axios.get('/dashboard/staff', {
      headers: { 'Cache-Control': 'no-cache' },
      params,
    });
    
    staffList.value = response.data.staff || [];
    branches.value = response.data.branches || [];
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to load staff records. Please try again later.';
    console.error('Error fetching staff:', err);
    staffList.value = [];
    branches.value = [];
  } finally {
    isLoading.value = false;
  }
};

const filteredStaff = computed(() => {
  if (!Array.isArray(staffList.value)) return [];
  
  let filtered = staffList.value;
  
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(staff =>
      staff.first_name.toLowerCase().includes(query) ||
      staff.last_name.toLowerCase().includes(query) ||
      staff.email.toLowerCase().includes(query) ||
      staff.phone_number.includes(query)
    );
  }
  
  if (selectedBranch.value !== 'All Branches') {
    filtered = filtered.filter(staff => 
      staff.branches && staff.branches.some(branch => branch.name === selectedBranch.value)
    );
  }
  
  return filtered;
});

const paginatedStaff = computed(() => {
  const start = (currentPage.value - 1) * rowsPerPage.value;
  const end = start + rowsPerPage.value;
  return filteredStaff.value.slice(start, end);
});

const totalPages = computed(() => {
  return Math.max(1, Math.ceil(filteredStaff.value.length / rowsPerPage.value));
});

const handleSearch = () => { 
  currentPage.value = 1; 
};

const handleBranchFilter = async () => { 
  currentPage.value = 1;
  await fetchStaffRecords();
};

const handlePageChange = (page: number) => { 
  if (page >= 1 && page <= totalPages.value) currentPage.value = page; 
};

const getBranchNames = (staff: Staff): string => {
  if (!staff.branches || staff.branches.length === 0) return 'N/A';
  return staff.branches.map(b => b.name).join(', ');
};

const handleViewStaff = (staffId: string) => {
  const staff = staffList.value.find(s => s.staff_id === staffId);
  selectedStaff.value = staff || null;
  showViewModal.value = true;
};

const handleEditStaff = (staffId: string) => {
  const staff = staffList.value.find(s => s.staff_id === staffId);
  if (staff) {
    editForm.value = {
      staff_id: staff.staff_id,
      firstName: staff.first_name,
      lastName: staff.last_name,
      email: staff.email,
      phoneNumber: staff.phone_number,
      position: staff.position,
      branch_id: staff.branches[0]?.branch_id || ''
    };
  }
  showEditModal.value = true;
};

const closeViewModal = () => {
  showViewModal.value = false;
  selectedStaff.value = null;
};

const closeEditModal = () => {
  showEditModal.value = false;
  editForm.value = {
    staff_id: '',
    firstName: '',
    lastName: '',
    email: '',
    phoneNumber: '',
    position: '',
    branch_id: ''
  };
};

const saveStaff = async () => {
  try {
    await axios.put(`/dashboard/staff/${editForm.value.staff_id}`, editForm.value);
    await fetchStaffRecords();
    closeEditModal();
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to save staff. Please try again.';
    console.error('Error saving staff:', err);
  }
};

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

const prevPage = () => {
  if (currentPage.value > 1) currentPage.value--;
};

const nextPage = () => {
  if (currentPage.value < totalPages.value) currentPage.value++;
};
</script>

<template>
  <Head title="Staff Records" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Staff Recordsss</h1>
      </div>

      <div v-if="isLoading" class="text-center py-4 text-gray-500">
        Loading staff records...
      </div>
      <div v-else-if="error" class="text-center py-4 text-red-500">
        {{ error }}
      </div>
      <div v-else class="bg-white rounded-lg shadow-md p-6">
        <div class="flex flex-col sm:flex-row gap-4 mb-6">
          <div class="flex-1">
            <input
              v-model="searchQuery"
              @input="handleSearch"
              type="text"
              placeholder="Search by name, email, or phone..."
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
            />
          </div>
          <div class="flex gap-2">
            <select
              v-model="selectedBranch"
              @change="handleBranchFilter"
              class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
            >
              <option>All Branches</option>
              <option v-for="branch in branches" :key="branch.branch_id" :value="branch.name">
                {{ branch.name }}
              </option>
            </select>
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
                <th class="px-4 py-2 text-left">Email</th>
                <th class="px-4 py-2 text-left">Phone</th>
                <th class="px-4 py-2 text-left">Position</th>
                <th class="px-4 py-2 text-left">Branch</th>
                <th class="px-4 py-2 text-left">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="paginatedStaff.length === 0">
                <td colspan="6" class="text-center py-8 text-gray-500 text-lg">
                  No staff records found.
                </td>
              </tr>
              <tr
                v-for="staff in paginatedStaff"
                :key="staff.staff_id"
                class="border-b last:border-b-0 hover:bg-gray-50"
              >
                <td class="px-4 py-2">
                  <div class="font-medium">{{ staff.last_name }},</div>
                  <div class="text-sm text-gray-500">{{ staff.first_name }}</div>
                </td>
                <td class="px-4 py-2">{{ staff.email }}</td>
                <td class="px-4 py-2">{{ staff.phone_number }}</td>
                <td class="px-4 py-2">{{ staff.position }}</td>
                <td class="px-4 py-2">{{ getBranchNames(staff) }}</td>
                <td class="px-4 py-2">
                  <div class="flex gap-2">
                    <button
                      @click="handleViewStaff(staff.staff_id)"
                      class="bg-gray-600 text-white px-3 py-1 rounded hover:bg-gray-700 transition text-sm"
                    >
                      View
                    </button>
                    <button
                      @click="handleEditStaff(staff.staff_id)"
                      class="bg-darkGreen-900 text-white px-3 py-1 rounded hover:bg-darkGreen-800 transition text-sm"
                    >
                      Edit
                    </button>
                  </div>
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

      <!-- View Staff Modal -->
      <div
        v-if="showViewModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/20 backdrop-blur-sm"
      >
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-900">Staff Details</h2>
            <button @click="closeViewModal" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">
              &times;
            </button>
          </div>
          <div v-if="selectedStaff" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">First Name</label>
              <p class="text-gray-900">{{ selectedStaff.first_name }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Last Name</label>
              <p class="text-gray-900">{{ selectedStaff.last_name }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Email</label>
              <p class="text-gray-900">{{ selectedStaff.email }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Phone Number</label>
              <p class="text-gray-900">{{ selectedStaff.phone_number }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Position</label>
              <p class="text-gray-900">{{ selectedStaff.position }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Branch(es)</label>
              <p class="text-gray-900">{{ getBranchNames(selectedStaff) }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Created At</label>
              <p class="text-gray-900">{{ formatDateTime(selectedStaff.created_at) }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Last Updated</label>
              <p class="text-gray-900">{{ formatDateTime(selectedStaff.updated_at) }}</p>
            </div>
            <div class="flex justify-end pt-4">
              <button
                @click="closeViewModal"
                class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 transition-colors duration-200"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Edit Staff Modal -->
      <div
        v-if="showEditModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/20 backdrop-blur-sm"
      >
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-900">Edit Staff Record</h2>
            <button @click="closeEditModal" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">
              &times;
            </button>
          </div>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
              <input
                v-model="editForm.firstName"
                type="text"
                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
              <input
                v-model="editForm.lastName"
                type="text"
                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
              <input
                v-model="editForm.email"
                type="email"
                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
              <input
                v-model="editForm.phoneNumber"
                type="text"
                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Position</label>
              <select
                v-model="editForm.position"
                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
              >
                <option value="">Select Position</option>
                <option value="Receptionist">Receptionist</option>
                <option value="Dental Assistant">Dental Assistant</option>
                <option value="Office Staff">Office Staff</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
              <select
                v-model="editForm.branch_id"
                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
              >
                <option value="">Select Branch</option>
                <option v-for="branch in branches" :key="branch.branch_id" :value="branch.branch_id">
                  {{ branch.name }}
                </option>
              </select>
            </div>
          </div>
          <div class="flex justify-end gap-3 mt-6">
            <button
              @click="closeEditModal"
              class="px-4 py-2 text-gray-700 bg-gray-200 rounded hover:bg-gray-300 transition"
            >
              Cancel
            </button>
            <button
              @click="saveStaff"
              class="px-4 py-2 text-white bg-darkGreen-900 rounded hover:bg-darkGreen-800 transition"
            >
              Save Changes
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

.text-darkGreen-900 {
  color: #1e4f4f;
}

.bg-darkGreen-800 {
  background-color: #2d5f5c;
}
</style>