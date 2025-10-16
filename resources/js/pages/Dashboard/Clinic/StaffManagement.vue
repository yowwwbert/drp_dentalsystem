<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { Users, Stethoscope, UserCircle, Search, Filter, ChevronLeft, ChevronRight, Eye, Plus } from 'lucide-vue-next';

// Interfaces
interface Dentist {
  dentist_id: string;
  first_name: string;
  last_name: string;
  email_address: string;
  phone_number: string;
  dentist_type: string;
  branch_name: string;
  branch_id: string | null;
  status: string;
}

interface Receptionist {
  staff_id: string;
  first_name: string;
  last_name: string;
  email: string;
  phone_number: string;
  position: string;
  branches: Array<{ branch_id: string; branch_name: string }>;
  created_at: string;
  updated_at: string;
}

interface Branch {
  branch_id: string;
  branch_name: string;
}

interface DentistData {
  dentists: Dentist[];
  branches: Branch[];
  pagination: {
    currentPage: number;
    totalPages: number;
    perPage: number;
    totalRecords: number;
  };
}

interface ReceptionistData {
  staff: Receptionist[];
  branches: Branch[];
}

// Breadcrumbs
const { props } = usePage() as { props: { auth: { user: { user_type: string } }; route?: any } };
const isOwner = computed(() => props.auth.user.user_type === 'Owner');
const breadcrumbs = computed<BreadcrumbItem[]>(() => [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Staff Management', href: isOwner.value ? '/dashboard/owner/records/StaffManagement' : '/dashboard/staff/records/StaffManagement' },
]);

// State
const activeTab = ref<'manage' | 'dentists' | 'receptionists'>('manage');
const searchQuery = ref('');
const selectedBranch = ref('All Branches');
const currentPage = ref(1);
const itemsPerPage = ref(10);
const loading = ref({ dentists: false, receptionists: false, branchUpdate: false });
const error = ref<string | null>(null);
const dentistData = ref<DentistData | null>(null);
const receptionistData = ref<ReceptionistData | null>(null);
const showBranchModal = ref(false);
const selectedStaff = ref<{ id: string; type: 'dentist' | 'receptionist'; name: string; currentBranch: string; position: string } | null>(null);
const newBranchId = ref('');
const staffSearchQuery = ref('');
const showStaffDropdown = ref(false);

// Route helper
const route = (name: string, params: Record<string, any> = {}) => {
  const routeHelper = typeof (window as any).route === 'function'
    ? (window as any).route
    : props.route;
  return routeHelper(name, params);
};

// Data fetching
const fetchDentistData = async () => {
  try {
    loading.value.dentists = true;
    const response = await axios.get(route('owner.dentists.data'), {
      headers: { 'Cache-Control': 'no-cache' },
    });
    console.log('Raw dentist API response:', response.data);
    dentistData.value = {
      ...response.data,
      dentists: response.data.dentists.map((d: any) => ({
        dentist_id: d.dentist_id,
        first_name: d.first_name,
        last_name: d.last_name,
        email_address: d.email_address,
        phone_number: d.phone_number,
        dentist_type: d.dentist_type,
        branch_name: d.branch_name || 'N/A',
        branch_id: d.branch_id || null,
        status: d.status,
      })),
      branches: response.data.branches
        .filter((b: any) => b.branch_id && typeof b.branch_id === 'string')
        .map((b: any) => ({
          branch_id: b.branch_id,
          branch_name: b.branch_name || 'Unknown',
        })),
    };
    console.log('Processed dentist branches:', dentistData.value.branches);
  } catch (err: any) {
    error.value = 'Failed to fetch dentist data';
    console.error('Error fetching dentist data:', {
      message: err.message,
      response: err.response?.data,
      status: err.response?.status,
    });
  } finally {
    loading.value.dentists = false;
  }
};

const fetchReceptionistData = async () => {
  if (!isOwner.value) {
    console.warn('Skipping receptionist data fetch: User is not an Owner');
    return;
  }
  const params: Record<string, string> = {};
  try {
    loading.value.receptionists = true;
    if (selectedBranch.value !== 'All Branches') {
      const branch = allBranches.value.find(b => b.branch_name === selectedBranch.value);
      if (!branch) {
        error.value = `Selected branch "${selectedBranch.value}" not found`;
        console.warn(`Branch "${selectedBranch.value}" not found in allBranches`, allBranches.value);
        return;
      }
      params.branch_id = branch.branch_id;
    }
    const response = await axios.get(route('staff.get'), { params, headers: { 'Cache-Control': 'no-cache' } });
    console.log('Raw receptionist API response:', response.data);
    receptionistData.value = {
      staff: response.data.staff.map((s: any) => ({
        staff_id: s.staff_id,
        first_name: s.first_name,
        last_name: s.last_name,
        email_address: s.email_address,
        phone_number: s.phone_number,
        position: s.position,
        branches: s.branches
          .filter((b: any) => b.branch_id && typeof b.branch_id === 'string')
          .map((b: any) => ({
            branch_id: b.branch_id,
            branch_name: b.branch_name || 'Unknown',
          })),
        created_at: s.created_at,
        updated_at: s.updated_at,
      })),
      branches: response.data.branches
        .filter((b: any) => b.branch_id && typeof b.branch_id === 'string')
        .map((b: any) => ({
          branch_id: b.branch_id,
          branch_name: b.branch_name || 'Unknown',
        })),
    };
    console.log('Processed receptionist branches:', receptionistData.value.branches);
  } catch (err: any) {
    let errorMessage = 'Failed to fetch receptionist data';
    if (err.response) {
      errorMessage = err.response.data?.message || err.response.data?.error || errorMessage;
    }
    error.value = errorMessage;
    console.error('Error fetching receptionist data:', {
      message: err.message,
      response: err.response?.data,
      status: err.response?.status,
      params: params || 'Not defined',
    });
  } finally {
    loading.value.receptionists = false;
  }
};

// Branch reassignment
const allStaff = computed(() => {
  const dentists = dentistData.value?.dentists.map(d => ({
    id: d.dentist_id,
    type: 'dentist' as const,
    name: `${d.first_name} ${d.last_name}`,
    currentBranch: d.branch_name || 'N/A',
    position: d.dentist_type,
  })) || [];
  const receptionists = isOwner.value
    ? receptionistData.value?.staff.map(r => ({
        id: r.staff_id,
        type: 'receptionist' as const,
        name: `${r.first_name} ${r.last_name}`,
        currentBranch: r.branches[0]?.branch_name || 'N/A',
        position: r.position,
      })) || []
    : [];
  return [...dentists, ...receptionists];
});

const filteredStaff = computed(() => {
  if (!staffSearchQuery.value) return [];
  const query = staffSearchQuery.value.toLowerCase();
  return allStaff.value.filter(staff =>
    staff.name.toLowerCase().includes(query)
  ).slice(0, 10); // Limit to 10 results
});

const selectStaff = (staff: { id: string; type: 'dentist' | 'receptionist'; name: string; currentBranch: string; position: string }) => {
  selectedStaff.value = staff;
  staffSearchQuery.value = staff.name;
  showStaffDropdown.value = false;
  newBranchId.value = '';
};

const openBranchModal = (staff: { id: string; type: 'dentist' | 'receptionist'; name: string; currentBranch: string; position: string }) => {
  selectedStaff.value = staff;
  staffSearchQuery.value = staff.name;
  newBranchId.value = '';
  showBranchModal.value = true;
};

const closeBranchModal = () => {
  showBranchModal.value = false;
  selectedStaff.value = null;
  newBranchId.value = '';
  staffSearchQuery.value = '';
};

const saveBranchChange = async () => {
  if (!selectedStaff.value || !newBranchId.value) {
    error.value = 'Please select a staff member and a branch';
    console.error('Missing selectedStaff or newBranchId:', { selectedStaff: selectedStaff.value, newBranchId: newBranchId.value });
    return;
  }
  if (!allBranches.value.find(b => b.branch_id === newBranchId.value)) {
    error.value = 'Invalid branch selected';
    console.error('Selected branch_id not found:', newBranchId.value, 'Available branches:', allBranches.value);
    return;
  }
  try {
    loading.value.branchUpdate = true;
    const userType = selectedStaff.value.type;
    const url = route('staff.branch.assign', { id: selectedStaff.value.id });
    const payload = {
      branch_id: newBranchId.value,
      user_type: userType,
    };
    console.log('Sending POST request:', { url, payload, selectedStaff: selectedStaff.value });
    const response = await axios.post(url, payload, {
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
      },
    });
    console.log('Branch assignment response:', response.data);
    if (response.data.success && response.data.message.includes('assigned successfully')) {
      const branchName = response.data.branch_name || 'Unknown';
      // Re-fetch data to ensure consistency
      if (userType === 'dentist') {
        await fetchDentistData();
      } else if (userType === 'receptionist' && isOwner.value) {
        await fetchReceptionistData();
      }
      // Update local state as fallback
      if (userType === 'receptionist' && receptionistData.value && isOwner.value) {
        const index = receptionistData.value.staff.findIndex(s => s.staff_id === selectedStaff.value!.id);
        if (index !== -1) {
          const staff = receptionistData.value.staff[index];
          const updatedBranches = [
            ...staff.branches.filter(b => b.branch_id !== newBranchId.value),
            { branch_id: newBranchId.value, branch_name: branchName },
          ];
          receptionistData.value.staff[index] = {
            ...staff,
            branches: updatedBranches,
          };
        }
      } else if (userType === 'dentist' && dentistData.value) {
        const index = dentistData.value.dentists.findIndex(d => d.dentist_id == selectedStaff.value!.id);
        if (index !== -1) {
          const dentist = dentistData.value.dentists[index];
          dentistData.value.dentists[index] = {
            ...dentist,
            branch_name: branchName,
            branch_id: newBranchId.value,
          };
        }
      }
      console.log('Updated state after branch assignment:', {
        userType,
        branchId: newBranchId.value,
        branchName,
      });
      closeBranchModal();
    } else {
      error.value = 'Unexpected response from server';
      console.error('Unexpected response:', response.data);
    }
  } catch (err: any) {
    const errorMessage = err.response?.status === 409
      ? `This ${selectedStaff.value.type} is already assigned to the selected branch`
      : err.response?.data?.message || `Failed to assign ${selectedStaff.value.type} branch`;
    error.value = errorMessage;
    console.error('Error assigning branch:', {
      message: err.message,
      response: err.response?.data,
      status: err.response?.status,
      url,
      payload,
    });
  } finally {
    loading.value.branchUpdate = false;
  }
};

// Computed properties
const allBranches = computed(() => {
  const dentistBranches = dentistData.value?.branches || [];
  const receptionistBranches = isOwner.value ? receptionistData.value?.branches || [] : [];
  const uniqueBranches = new Map<string, Branch>();
  [...dentistBranches, ...receptionistBranches].forEach(branch => {
    if (!branch.branch_id) {
      console.warn('Skipping branch with undefined branch_id:', branch);
      return;
    }
    if (uniqueBranches.has(branch.branch_id)) {
      console.warn(`Duplicate branch_id detected: ${branch.branch_id}`, {
        existing: uniqueBranches.get(branch.branch_id),
        new: branch,
      });
      if (uniqueBranches.get(branch.branch_id)!.branch_name !== branch.branch_name) {
        console.warn(`Name mismatch for branch_id ${branch.branch_id}: keeping existing name`);
      }
    } else {
      uniqueBranches.set(branch.branch_id, branch);
    }
  });
  return Array.from(uniqueBranches.values());
});

// Filter branches for the modal to exclude current branch
const availableBranches = computed(() => {
  if (!selectedStaff.value || selectedStaff.value.currentBranch === 'N/A') {
    return allBranches.value;
  }
  return allBranches.value.filter(branch => branch.branch_name !== selectedStaff.value!.currentBranch);
});

// Click outside handler for dropdown
onMounted(() => {
  fetchDentistData();
  if (isOwner.value) fetchReceptionistData();
  
  // Close dropdown when clicking outside
  document.addEventListener('click', (e) => {
    const target = e.target as HTMLElement;
    if (!target.closest('.relative')) {
      showStaffDropdown.value = false;
    }
  });
});

// Computed properties for filtering
const filteredDentists = computed(() => {
  if (!dentistData.value) return [];
  let filtered = dentistData.value.dentists;
  if (searchQuery.value) {
    filtered = filtered.filter(dentist =>
      dentist.first_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      dentist.last_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      dentist.email_address.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      dentist.phone_number.includes(searchQuery.value)
    );
  }
  if (selectedBranch.value !== 'All Branches') {
    filtered = filtered.filter(dentist => dentist.branch_name === selectedBranch.value);
  }
  return filtered;
});

const filteredReceptionists = computed(() => {
  if (!receptionistData.value || !isOwner.value) return [];
  let filtered = receptionistData.value.staff;
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
      staff.branches.some(branch => branch.branch_name === selectedBranch.value)
    );
  }
  return filtered;
});

const paginatedRecords = computed(() => {
  const records = activeTab.value === 'dentists' ? filteredDentists.value : filteredReceptionists.value;
  const start = (currentPage.value - 1) * itemsPerPage.value;
  const end = start + itemsPerPage.value;
  return records.slice(start, end);
});

const totalPages = computed(() => {
  const records = activeTab.value === 'dentists' ? filteredDentists.value : filteredReceptionists.value;
  return Math.max(1, Math.ceil(records.length / itemsPerPage.value));
});

// Stats for manage tab
const totalDentists = computed(() => dentistData.value?.dentists.length || 0);
const totalReceptionists = computed(() => receptionistData.value?.staff.length || 0);
const totalStaff = computed(() => totalDentists.value + totalReceptionists.value);

// Event handlers
const handleSearch = () => { currentPage.value = 1; };
const handleBranchFilter = async () => {
  currentPage.value = 1;
  if (activeTab.value === 'receptionists' && isOwner.value) await fetchReceptionistData();
};
const handlePageChange = (page: number) => {
  if (page >= 1 && page <= totalPages.value) currentPage.value = page;
};

const viewRecord = (record: Dentist | Receptionist) => {
  if ('dentist_id' in record) {
    router.visit(route('owner.dentist.records', { dentist_id: record.dentist_id }));
  } else if (isOwner.value) {
    router.visit(`/dashboard/staff/${record.staff_id}`);
  }
};

const addDentist = () => {
  router.visit(route('owner.register-staff', { user_type: 'Dentist' }));
};

const addStaff = () => {
  router.visit(route('owner.register-staff', { user_type: 'Staff' }));
};

const formatDateTime = (dateTime: string): string =>
  dateTime
    ? new Date(dateTime).toLocaleString('en-US', { month: 'long', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' })
    : 'N/A';
</script>

<template>
  <Head title="Staff Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="min-h-screen bg-gray-50 dark:bg-neutral-900 transition-colors duration-300 rounded-xl mt-2 p-4">
      <div class="px-4 py-4">
        <!-- Header -->
        <div class="mb-8">
          <div class="flex items-center gap-3 mb-2">
            <h1 class="text-3xl font-bold text-neutral-900 dark:text-white">Staff Management</h1>
          </div>
          <p class="text-neutral-600 dark:text-neutral-400">Manage dentists and receptionists across all branches</p>
        </div>

        <!-- Loading State -->
        <div v-if="loading.dentists || (isOwner && loading.receptionists)" class="flex justify-center items-center py-16">
          <div class="animate-spin rounded-full h-12 w-12 border-4 border-[#1e4f4f] border-t-transparent"></div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="bg-red-50 dark:bg-red-950/20 border-l-4 border-red-600 rounded-lg p-6">
          <p class="text-red-800 dark:text-red-300 mb-4">{{ error }}</p>
          <button 
            @click="() => { fetchDentistData(); if (isOwner) fetchReceptionistData(); }" 
            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium"
          >
            Try Again
          </button>
        </div>

        <!-- Main Content -->
        <div v-else class="space-y-6">
          <!-- Tabs -->
          <div class="bg-white dark:bg-neutral-900 rounded-xl border border-neutral-200 dark:border-neutral-800 shadow-sm p-1">
            <div class="flex gap-1">
              <button
                @click="activeTab = 'manage'; currentPage = 1"
                :class="[
                  'flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-lg font-medium transition',
                  activeTab === 'manage' 
                    ? 'bg-[#1e4f4f] text-white shadow-sm' 
                    : 'text-neutral-600 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-neutral-800'
                ]"
              >
                <Users :size="20" />
                Manage
              </button>
              <button
                @click="activeTab = 'dentists'; currentPage = 1"
                :class="[
                  'flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-lg font-medium transition',
                  activeTab === 'dentists' 
                    ? 'bg-[#1e4f4f] text-white shadow-sm' 
                    : 'text-neutral-600 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-neutral-800'
                ]"
              >
                <Stethoscope :size="20" />
                Dentists
              </button>
              <button
                v-if="isOwner"
                @click="activeTab = 'receptionists'; currentPage = 1"
                :class="[
                  'flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-lg font-medium transition',
                  activeTab === 'receptionists' 
                    ? 'bg-[#1e4f4f] text-white shadow-sm' 
                    : 'text-neutral-600 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-neutral-800'
                ]"
              >
                <UserCircle :size="20" />
                Receptionists
              </button>
            </div>
          </div>

          <!-- Manage Tab Content -->
          <div v-if="activeTab === 'manage'" class="space-y-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div class="bg-white dark:bg-neutral-900 rounded-xl border border-neutral-200 dark:border-neutral-800 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                  <div class="p-3 bg-[#1e4f4f]/10 rounded-lg">
                    <Users :size="24" class="text-[#1e4f4f]" />
                  </div>
                </div>
                <h3 class="text-2xl font-bold text-neutral-900 dark:text-white mb-1">{{ totalStaff }}</h3>
                <p class="text-sm text-neutral-600 dark:text-neutral-400">Total Staff Members</p>
              </div>
              <div class="bg-white dark:bg-neutral-900 rounded-xl border border-neutral-200 dark:border-neutral-800 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                  <div class="p-3 bg-[#2d5f5c]/10 rounded-lg">
                    <Stethoscope :size="24" class="text-[#2d5f5c]" />
                  </div>
                </div>
                <h3 class="text-2xl font-bold text-neutral-900 dark:text-white mb-1">{{ totalDentists }}</h3>
                <p class="text-sm text-neutral-600 dark:text-neutral-400">Active Dentists</p>
              </div>
              <div class="bg-white dark:bg-neutral-900 rounded-xl border border-neutral-200 dark:border-neutral-800 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                  <div class="p-3 bg-[#3d6f6c]/10 rounded-lg">
                    <UserCircle :size="24" class="text-[#3d6f6c]" />
                  </div>
                </div>
                <h3 class="text-2xl font-bold text-neutral-900 dark:text-white mb-1">{{ totalReceptionists }}</h3>
                <p class="text-sm text-neutral-600 dark:text-neutral-400">Active Receptionists</p>
              </div>
            </div>

            <!-- Branch Reassignment Info -->
            <div class="bg-white dark:bg-neutral-900 rounded-xl border border-neutral-200 dark:border-neutral-800 shadow-sm p-6">
              <h3 class="text-lg font-semibold text-neutral-900 dark:text-white mb-4">Quick Branch Reassignment</h3>
              <p class="text-sm text-neutral-600 dark:text-neutral-400 mb-4">
                Search for a staff member and quickly reassign them to a different branch
              </p>
              
              <div class="space-y-4">
                <!-- Staff Search -->
                <div class="relative">
                  <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">Search Staff Member</label>
                  <div class="relative">
                    <Search :size="20" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-neutral-400" />
                    <input
                      v-model="staffSearchQuery"
                      @focus="showStaffDropdown = true"
                      @input="showStaffDropdown = true"
                      type="text"
                      placeholder="Type staff name..."
                      class="w-full pl-10 pr-4 py-2.5 border border-neutral-300 dark:border-neutral-700 rounded-lg bg-white dark:bg-neutral-800 text-neutral-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1e4f4f] focus:border-transparent"
                    />
                  </div>
                  
                  <!-- Dropdown Results -->
                  <div 
                    v-if="showStaffDropdown && staffSearchQuery && filteredStaff.length > 0"
                    class="absolute z-10 w-full mt-1 bg-white dark:bg-neutral-800 border border-neutral-300 dark:border-neutral-700 rounded-lg shadow-lg max-h-60 overflow-y-auto"
                  >
                    <button
                      v-for="staff in filteredStaff"
                      :key="staff.id"
                      @click="selectStaff(staff)"
                      class="w-full px-4 py-3 text-left hover:bg-neutral-100 dark:hover:bg-neutral-700 transition flex items-center justify-between"
                    >
                      <div>
                        <p class="text-neutral-900 dark:text-white font-medium">{{ staff.name }}</p>
                        <p class="text-sm text-neutral-600 dark:text-neutral-400">
                          {{ staff.position }} • {{ staff.currentBranch }}
                        </p>
                      </div>
                      <span class="text-xs px-2 py-1 rounded-full bg-[#1e4f4f]/10 text-[#1e4f4f]">
                        {{ staff.type === 'dentist' ? 'Dentist' : 'Receptionist' }}
                      </span>
                    </button>
                  </div>
                  
                  <!-- No Results -->
                  <div 
                    v-if="showStaffDropdown && staffSearchQuery && filteredStaff.length === 0"
                    class="absolute z-10 w-full mt-1 bg-white dark:bg-neutral-800 border border-neutral-300 dark:border-neutral-700 rounded-lg shadow-lg p-4"
                  >
                    <p class="text-neutral-600 dark:text-neutral-400 text-sm">No staff members found</p>
                  </div>
                </div>

                <!-- Current Branch Display -->
                <div v-if="selectedStaff">
                  <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">Current Branch</label>
                  <div class="px-4 py-2.5 bg-neutral-100 dark:bg-neutral-800 rounded-lg text-neutral-700 dark:text-neutral-300">
                    {{ selectedStaff.currentBranch }}
                  </div>
                </div>

                <!-- New Branch Selection -->
                <div v-if="selectedStaff">
                  <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">New Branch</label>
                  <div class="relative">
                    <Filter :size="20" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-neutral-400" />
                    <select
                      v-model="newBranchId"
                      class="w-full pl-10 pr-4 py-2.5 border border-neutral-300 dark:border-neutral-700 rounded-lg bg-white dark:bg-neutral-800 text-neutral-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1e4f4f] focus:border-transparent"
                    >
                      <option value="" disabled>Select a branch</option>
                      <option v-for="branch in availableBranches" :key="branch.branch_id" :value="branch.branch_id">
                        {{ branch.branch_name }}
                      </option>
                    </select>
                  </div>
                </div>

                <!-- Action Buttons -->
                <div v-if="selectedStaff" class="flex gap-3 pt-2">
                  <button
                    @click="saveBranchChange"
                    :disabled="!newBranchId || loading.branchUpdate"
                    class="flex-1 px-4 py-2.5 bg-[#1e4f4f] text-white rounded-lg hover:bg-[#2d5f5c] transition font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <span v-if="loading.branchUpdate" class="flex items-center justify-center gap-2">
                      <div class="animate-spin rounded-full h-5 w-5 border-2 border-white border-t-transparent"></div>
                      Assigning...
                    </span>
                    <span v-else>Assign Branch</span>
                  </button>
                  <button
                    @click="() => { selectedStaff = null; staffSearchQuery = ''; newBranchId = ''; }"
                    class="px-4 py-2.5 bg-neutral-200 dark:bg-neutral-700 text-neutral-700 dark:text-neutral-300 rounded-lg hover:bg-neutral-300 dark:hover:bg-neutral-600 transition"
                  >
                    Clear
                  </button>
                </div>
              </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white dark:bg-neutral-900 rounded-xl border border-neutral-200 dark:border-neutral-800 shadow-sm p-6">
              <h3 class="text-lg font-semibold text-neutral-900 dark:text-white mb-4">Quick Actions</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <button 
                  @click="activeTab = 'dentists'"
                  class="flex items-center gap-3 p-4 bg-[#1e4f4f]/5 hover:bg-[#1e4f4f]/10 rounded-lg transition group"
                >
                  <div class="p-2 bg-[#1e4f4f] text-white rounded-lg group-hover:scale-110 transition">
                    <Stethoscope :size="20" />
                  </div>
                  <div class="text-left">
                    <p class="font-medium text-neutral-900 dark:text-white">Manage Dentists</p>
                    <p class="text-sm text-neutral-600 dark:text-neutral-400">View and manage dentist records</p>
                  </div>
                </button>
                <button 
                  v-if="isOwner"
                  @click="activeTab = 'receptionists'"
                  class="flex items-center gap-3 p-4 bg-[#2d5f5c]/5 hover:bg-[#2d5f5c]/10 rounded-lg transition group"
                >
                  <div class="p-2 bg-[#2d5f5c] text-white rounded-lg group-hover:scale-110 transition">
                    <UserCircle :size="20" />
                  </div>
                  <div class="text-left">
                    <p class="font-medium text-neutral-900 dark:text-white">Manage Receptionists</p>
                    <p class="text-sm text-neutral-600 dark:text-neutral-400">View and manage receptionist records</p>
                  </div>
                </button>
                <button 
                  v-if="isOwner"
                  @click="addDentist"
                  class="flex items-center gap-3 p-4 bg-[#1e4f4f]/5 hover:bg-[#1e4f4f]/10 rounded-lg transition group"
                >
                  <div class="p-2 bg-[#1e4f4f] text-white rounded-lg group-hover:scale-110 transition">
                    <Plus :size="20" />
                  </div>
                  <div class="text-left">
                    <p class="font-medium text-neutral-900 dark:text-white">Add Dentist</p>
                    <p class="text-sm text-neutral-600 dark:text-neutral-400">Create a new dentist account</p>
                  </div>
                </button>
                <button 
                  v-if="isOwner"
                  @click="addStaff"
                  class="flex items-center gap-3 p-4 bg-[#2d5f5c]/5 hover:bg-[#2d5f5c]/10 rounded-lg transition group"
                >
                  <div class="p-2 bg-[#2d5f5c] text-white rounded-lg group-hover:scale-110 transition">
                    <Plus :size="20" />
                  </div>
                  <div class="text-left">
                    <p class="font-medium text-neutral-900 dark:text-white">Add Staff</p>
                    <p class="text-sm text-neutral-600 dark:text-neutral-400">Create a new staff account</p>
                  </div>
                </button>
              </div>
            </div>
          </div>

          <!-- Dentists/Receptionists Tab Content -->
          <div v-else class="space-y-6">
            <!-- Filters -->
            <div class="bg-white dark:bg-neutral-900 rounded-xl border border-neutral-200 dark:border-neutral-800 shadow-sm p-4">
              <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1 relative">
                  <Search :size="20" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-neutral-400" />
                  <input
                    v-model="searchQuery"
                    @input="handleSearch"
                    type="text"
                    placeholder="Search by name, email, or phone..."
                    class="w-full pl-10 pr-4 py-2.5 border border-neutral-300 dark:border-neutral-700 rounded-lg bg-white dark:bg-neutral-800 text-neutral-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1e4f4f] focus:border-transparent"
                  />
                </div>
                <div class="relative sm:w-64">
                  <Filter :size="20" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-neutral-400" />
                  <select
                    v-model="selectedBranch"
                    @change="handleBranchFilter"
                    class="w-full pl-10 pr-4 py-2.5 border border-neutral-300 dark:border-neutral-700 rounded-lg bg-white dark:bg-neutral-800 text-neutral-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1e4f4f] focus:border-transparent appearance-none"
                  >
                    <option>All Branches</option>
                    <option v-for="branch in allBranches" :key="branch.branch_id" :value="branch.branch_name">
                      {{ branch.branch_name }}
                    </option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-neutral-900 rounded-xl border border-neutral-200 dark:border-neutral-800 shadow-sm overflow-hidden">
              <div class="overflow-x-auto">
                <table class="w-full min-w-[900px]">
                  <thead>
                    <tr class="bg-[#1e4f4f]">
                      <th class="py-4 px-6 text-left text-white font-semibold">Last Name</th>
                      <th class="py-4 px-6 text-left text-white font-semibold">First Name</th>
                      <th class="py-4 px-6 text-left text-white font-semibold">Email</th>
                      <th class="py-4 px-6 text-left text-white font-semibold">Phone</th>
                      <th class="py-4 px-6 text-left text-white font-semibold">Position</th>
                      <th class="py-4 px-6 text-left text-white font-semibold">Branch</th>
                      <th class="py-4 px-6 text-left text-white font-semibold">Action</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-neutral-200 dark:divide-neutral-800">
                    <tr v-if="paginatedRecords.length === 0">
                      <td colspan="7" class="text-center py-16">
                        <div class="flex flex-col items-center gap-3">
                          <div class="p-4 bg-neutral-100 dark:bg-neutral-800 rounded-full">
                            <Users :size="32" class="text-neutral-400" />
                          </div>
                          <p class="text-neutral-600 dark:text-neutral-400 text-lg font-medium">
                            No {{ activeTab === 'dentists' ? 'dentist' : 'receptionist' }} records found
                          </p>
                        </div>
                      </td>
                    </tr>
                    <tr 
                      v-for="record in paginatedRecords" 
                      :key="'dentist_id' in record ? record.dentist_id : record.staff_id" 
                      class="hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition"
                    >
                      <td class="py-4 px-6 text-neutral-900 dark:text-white font-medium">{{ record.last_name || 'N/A' }}</td>
                      <td class="py-4 px-6 text-neutral-900 dark:text-white">{{ record.first_name || 'N/A' }}</td>
                      <td class="py-4 px-6 text-neutral-600 dark:text-neutral-400">
                        {{ 'email_address' in record ? record.email_address : record.email || 'N/A' }}
                      </td>
                      <td class="py-4 px-6 text-neutral-600 dark:text-neutral-400">{{ record.phone_number || 'N/A' }}</td>
                      <td class="py-4 px-6">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-[#1e4f4f]/10 text-[#1e4f4f]">
                          {{ 'dentist_type' in record ? record.dentist_type : record.position || 'N/A' }}
                        </span>
                      </td>
                      <td class="py-4 px-6 text-neutral-600 dark:text-neutral-400">
                        {{ 'branch_name' in record ? record.branch_name : record.branches?.map(b => b.branch_name).join(', ') || 'N/A' }}
                      </td>
                      <td class="py-4 px-6">
                        <div class="flex gap-2">
                          <button
                            @click="viewRecord(record)"
                            class="flex items-center gap-2 px-4 py-2 bg-[#1e4f4f] text-white rounded-lg hover:bg-[#2d5f5c] transition font-medium"
                          >
                            <Eye :size="16" />
                            View
                          </button>
                          <button
                            @click="openBranchModal({
                              id: 'dentist_id' in record ? record.dentist_id : record.staff_id,
                              type: 'dentist_id' in record ? 'dentist' : 'receptionist',
                              name: `${record.first_name} ${record.last_name}`,
                              currentBranch: 'branch_name' in record ? record.branch_name : record.branches?.[0]?.branch_name || 'N/A',
                              position: 'dentist_type' in record ? record.dentist_type : record.position
                            })"
                            class="flex items-center gap-2 px-4 py-2 bg-neutral-200 dark:bg-neutral-700 text-neutral-700 dark:text-neutral-300 rounded-lg hover:bg-neutral-300 dark:hover:bg-neutral-600 transition"
                          >
                            Reassign
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Pagination -->
              <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center px-6 py-4 border-t border-neutral-200 dark:border-neutral-800 gap-4">
                <div class="flex items-center gap-2">
                  <span class="text-sm text-neutral-700 dark:text-neutral-300">Rows per page:</span>
                  <select
                    v-model="itemsPerPage"
                    @change="currentPage = 1"
                    class="border border-neutral-300 dark:border-neutral-700 rounded-lg px-3 py-1.5 text-sm bg-white dark:bg-neutral-800 text-neutral-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1e4f4f]"
                  >
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                  </select>
                </div>
                <div class="flex items-center gap-3">
                  <span class="text-sm text-neutral-700 dark:text-neutral-300">
                    Page {{ totalPages === 0 ? 0 : currentPage }} of {{ totalPages === 0 ? 0 : totalPages }}
                  </span>
                  <div class="flex gap-1">
                    <button
                      @click="handlePageChange(currentPage - 1)"
                      :disabled="currentPage === 1"
                      class="p-2 border border-neutral-300 dark:border-neutral-700 rounded-lg text-neutral-700 dark:text-neutral-300 bg-white dark:bg-neutral-800 hover:bg-neutral-50 dark:hover:bg-neutral-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
                    >
                      <ChevronLeft :size="20" />
                    </button>
                    <button
                      @click="handlePageChange(currentPage + 1)"
                      :disabled="currentPage === totalPages || totalPages === 0"
                      class="p-2 border border-neutral-300 dark:border-neutral-700 rounded-lg text-neutral-700 dark:text-neutral-300 bg-white dark:bg-neutral-800 hover:bg-neutral-50 dark:hover:bg-neutral-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
                    >
                      <ChevronRight :size="20" />
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Branch Reassignment Modal -->
          <div v-if="showBranchModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-neutral-900 rounded-xl border border-neutral-200 dark:border-neutral-800 shadow-xl p-6 w-full max-w-md mx-4">
              <h2 class="text-xl font-bold text-neutral-900 dark:text-white mb-4">Reassign Branch</h2>
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Staff Member</label>
                  <input
                    :value="selectedStaff?.name"
                    type="text"
                    disabled
                    class="w-full border border-neutral-300 dark:border-neutral-700 rounded-lg px-3 py-2 text-neutral-500 dark:text-neutral-400 bg-neutral-100 dark:bg-neutral-800"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Current Branch</label>
                  <input
                    :value="selectedStaff?.currentBranch"
                    type="text"
                    disabled
                    class="w-full border border-neutral-300 dark:border-neutral-700 rounded-lg px-3 py-2 text-neutral-500 dark:text-neutral-400 bg-neutral-100 dark:bg-neutral-800"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">New Branch</label>
                  <select
                    v-model="newBranchId"
                    class="w-full border border-neutral-300 dark:border-neutral-700 rounded-lg px-3 py-2 bg-white dark:bg-neutral-800 text-neutral-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1e4f4f]"
                  >
                    <option value="" disabled>Select a branch</option>
                    <option v-for="branch in availableBranches" :key="branch.branch_id" :value="branch.branch_id">
                      {{ branch.branch_name }}
                    </option>
                  </select>
                </div>
              </div>
              <div class="flex justify-end gap-3 mt-6">
                <button 
                  @click="closeBranchModal" 
                  class="px-4 py-2 text-neutral-700 dark:text-neutral-300 bg-neutral-200 dark:bg-neutral-700 rounded-lg hover:bg-neutral-300 dark:hover:bg-neutral-600 transition"
                >
                  Cancel
                </button>
                <button 
                  @click="saveBranchChange" 
                  :disabled="!newBranchId || loading.branchUpdate"
                  class="px-4 py-2 bg-[#1e4f4f] text-white rounded-lg hover:bg-[#2d5f5c] transition disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <span v-if="loading.branchUpdate" class="flex items-center gap-2">
                    <div class="animate-spin rounded-full h-5 w-5 border-2 border-white border-t-transparent"></div>
                    Assigning...
                  </span>
                  <span v-else>Assign Branch</span>
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
/* Custom scrollbar */
.overflow-x-auto::-webkit-scrollbar {
  height: 8px;
}

.overflow-x-auto::-webkit-scrollbar-track {
  background: transparent;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 4px;
}

.dark .overflow-x-auto::-webkit-scrollbar-thumb {
  background: #4b5563;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
  background: #9ca3af;
}

.dark .overflow-x-auto::-webkit-scrollbar-thumb:hover {
  background: #6b7280;
}
</style>