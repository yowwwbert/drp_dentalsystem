<script>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';

export default {
  components: { AppLayout, Head },
  data() {
    return {
      toothMarks: [],
      isLoading: false,
      error: null,
      showCreateModal: false,
      showEditModal: false,
      form: {
        mark_name: '',
        mark_color: '#3B82F6',
      },
      editToothMark: null,
    };
  },
  computed: {
    breadcrumbs() {
      return [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Tooth Marks', href: '/tooth-marks' },
      ];
    },
  },
  mounted() {
    this.fetchToothMarks();
  },
  methods: {
    async fetchToothMarks() {
      try {
        this.isLoading = true;
        const response = await axios.get('/tooth-marks/data');
        this.toothMarks = response.data.toothMarks;
        this.error = null;
      } catch (err) {
        this.toothMarks = [];
        this.error = err.response?.data?.error || 'Failed to fetch tooth marks.';
        console.error('Error fetching tooth marks:', err);
      } finally {
        this.isLoading = false;
      }
    },
    async createToothMark() {
      if (!this.form.mark_name || !this.form.mark_color) {
        this.error = 'Please fill in all required fields.';
        return;
      }
      try {
        this.isLoading = true;
        await axios.post('/tooth-marks', this.form);
        this.showCreateModal = false;
        this.form = { mark_name: '', mark_color: '#3B82F6' };
        this.error = null;
        await this.fetchToothMarks();
      } catch (err) {
        this.error = err.response?.data?.error || 'Failed to create tooth mark.';
        console.error('Error creating tooth mark:', err);
      } finally {
        this.isLoading = false;
      }
    },
    async openEditModal(toothMark) {
      this.editToothMark = { ...toothMark };
      this.showEditModal = true;
      this.error = null;
    },
    async updateToothMark() {
      if (!this.editToothMark || !this.editToothMark.mark_name || !this.editToothMark.mark_color) {
        this.error = 'Please fill in all required fields.';
        return;
      }
      try {
        this.isLoading = true;
        await axios.put(`/tooth-marks/${this.editToothMark.tooth_mark_id}`, this.editToothMark);
        this.showEditModal = false;
        this.editToothMark = null;
        this.error = null;
        await this.fetchToothMarks();
      } catch (err) {
        this.error = err.response?.data?.error || 'Failed to update tooth mark.';
        console.error('Error updating tooth mark:', err);
      } finally {
        this.isLoading = false;
      }
    },
    openCreateModal() {
      this.form = { mark_name: '', mark_color: '#3B82F6' };
      this.showCreateModal = true;
      this.error = null;
    },
    closeCreateModal() {
      this.showCreateModal = false;
      this.form = { mark_name: '', mark_color: '#3B82F6' };
      this.error = null;
    },
    closeEditModal() {
      this.showEditModal = false;
      this.editToothMark = null;
      this.error = null;
    },
  },
};
</script>

<template>
  <Head title="Tooth Marks" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="min-h-screen bg-gray-50 dark:bg-neutral-900 transition-colors duration-300 rounded-xl mt-2 p-4">
      <div class="px-4 py-4 mx-auto">
        <!-- Header Section -->
        <div class="mb-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4" :class="{ 'blur-sm': showCreateModal || showEditModal }">
          <div>
            <h1 class="text-4xl font-bold mb-2 text-gray-900 dark:text-white">
              Tooth Marks
            </h1>
            <p class="text-lg text-gray-600 dark:text-neutral-400">
              Manage dental marking colors and labels
            </p>
          </div>
          <button
            @click="openCreateModal"
            class="bg-darkGreen-900 hover:bg-darkGreen-800 text-white px-6 py-3.5 rounded-xl font-semibold shadow-lg transition-all duration-200 flex items-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add Tooth Mark
          </button>
        </div>

        <!-- Loading State -->
        <div v-if="isLoading && toothMarks.length === 0" class="text-center py-16 text-gray-600 dark:text-neutral-300">
          <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-t-darkGreen-900 border-r-darkGreen-900 border-b-transparent border-l-transparent"></div>
          <p class="mt-4 text-lg">Loading tooth marks...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error && toothMarks.length === 0" class="text-center py-16 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-300 rounded-2xl">
          <p class="text-lg">{{ error }}</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="toothMarks.length === 0" class="text-center py-20 rounded-2xl bg-white dark:bg-neutral-800 text-gray-500 dark:text-neutral-400" :class="{ 'blur-sm': showCreateModal || showEditModal }">
          <svg class="w-16 h-16 mx-auto mb-4 text-gray-400 dark:text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
          </svg>
          <p class="text-xl mb-2">No tooth marks found</p>
          <p class="text-sm">Create your first tooth mark to get started</p>
        </div>

        <!-- Cards Grid -->
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" :class="{ 'blur-sm': showCreateModal || showEditModal }">
          <div
            v-for="mark in toothMarks"
            :key="mark.tooth_mark_id"
            class="group relative rounded-2xl p-6 shadow-lg border bg-white dark:bg-neutral-800 border-gray-100 dark:border-neutral-700 transition-all duration-300 hover:scale-[1.02] hover:shadow-xl"
          >
            <!-- Mark Name (Primary) -->
            <div class="mb-4">
              <h3 class="font-bold text-2xl text-gray-900 dark:text-white line-clamp-2 mb-3">
                {{ mark.mark_name }}
              </h3>
              
              <!-- Color Display (Secondary) -->
              <div class="flex items-center gap-3">
                <div
                  class="w-12 h-12 rounded-lg shadow-sm border-2 border-gray-200 dark:border-neutral-600 flex-shrink-0"
                  :style="{ backgroundColor: mark.mark_color }"
                ></div>
                <span class="text-sm font-mono text-gray-600 dark:text-neutral-400">
                  {{ mark.mark_color }}
                </span>
              </div>
            </div>

            <!-- Actions -->
            <div class="pt-4 border-t border-gray-100 dark:border-neutral-700">
              <button
                @click="openEditModal(mark)"
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
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Create Tooth Mark</h2>
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
                  Mark Name <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.mark_name"
                  type="text"
                  placeholder="e.g., Cavity, Crown, Filling"
                  class="w-full px-4 py-3 rounded-xl border bg-white dark:bg-neutral-750 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-neutral-500 focus:border-darkGreen-900 dark:focus:border-darkGreen-400 focus:outline-none focus:ring-2 focus:ring-darkGreen-900/30 dark:focus:ring-darkGreen-400/30 transition-all duration-200"
                />
              </div>

              <div>
                <label class="block text-sm font-semibold mb-2 text-gray-700 dark:text-neutral-300">
                  Color <span class="text-red-500">*</span>
                </label>
                <div class="space-y-3">
                  <!-- Color Preview -->
                  <div
                    class="w-full h-20 rounded-xl shadow-inner border-2 border-gray-200 dark:border-neutral-700 transition-colors duration-200"
                    :style="{ backgroundColor: form.mark_color }"
                  ></div>
                  
                  <!-- Color Picker -->
                  <div class="flex items-center gap-3">
                    <input
                      v-model="form.mark_color"
                      type="color"
                      class="w-16 h-12 rounded-lg border-2 border-gray-300 dark:border-neutral-700 cursor-pointer"
                    />
                    <input
                      v-model="form.mark_color"
                      type="text"
                      placeholder="#000000"
                      class="flex-1 px-4 py-3 rounded-xl border bg-white dark:bg-neutral-750 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-neutral-500 focus:border-darkGreen-900 dark:focus:border-darkGreen-400 focus:outline-none focus:ring-2 focus:ring-darkGreen-900/30 dark:focus:ring-darkGreen-400/30 transition-all duration-200 font-mono text-sm"
                    />
                  </div>
                </div>
              </div>

              <div v-if="error" class="p-3 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-300 text-sm">
                {{ error }}
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
                  @click="createToothMark"
                  :disabled="isLoading"
                  class="px-6 py-2.5 rounded-xl font-semibold bg-darkGreen-900 hover:bg-darkGreen-800 text-white transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{ isLoading ? 'Creating...' : 'Create' }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Edit Modal -->
        <div v-if="showEditModal && editToothMark" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
          <div class="w-full max-w-md rounded-2xl shadow-2xl bg-white dark:bg-neutral-800">
            <div class="px-6 py-5 border-b border-gray-200 dark:border-neutral-700">
              <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Tooth Mark</h2>
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
                  Mark Name <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="editToothMark.mark_name"
                  type="text"
                  placeholder="e.g., Cavity, Crown, Filling"
                  class="w-full px-4 py-3 rounded-xl border bg-white dark:bg-neutral-750 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-neutral-500 focus:border-darkGreen-900 dark:focus:border-darkGreen-400 focus:outline-none focus:ring-2 focus:ring-darkGreen-900/30 dark:focus:ring-darkGreen-400/30 transition-all duration-200"
                />
              </div>

              <div>
                <label class="block text-sm font-semibold mb-2 text-gray-700 dark:text-neutral-300">
                  Color <span class="text-red-500">*</span>
                </label>
                <div class="space-y-3">
                  <!-- Color Preview -->
                  <div
                    class="w-full h-20 rounded-xl shadow-inner border-2 border-gray-200 dark:border-neutral-700 transition-colors duration-200"
                    :style="{ backgroundColor: editToothMark.mark_color }"
                  ></div>
                  
                  <!-- Color Picker -->
                  <div class="flex items-center gap-3">
                    <input
                      v-model="editToothMark.mark_color"
                      type="color"
                      class="w-16 h-12 rounded-lg border-2 border-gray-300 dark:border-neutral-700 cursor-pointer"
                    />
                    <input
                      v-model="editToothMark.mark_color"
                      type="text"
                      placeholder="#000000"
                      class="flex-1 px-4 py-3 rounded-xl border bg-white dark:bg-neutral-750 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-neutral-500 focus:border-darkGreen-900 dark:focus:border-darkGreen-400 focus:outline-none focus:ring-2 focus:ring-darkGreen-900/30 dark:focus:ring-darkGreen-400/30 transition-all duration-200 font-mono text-sm"
                    />
                  </div>
                </div>
              </div>

              <div v-if="error" class="p-3 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-300 text-sm">
                {{ error }}
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
                  @click="updateToothMark"
                  :disabled="isLoading"
                  class="px-6 py-2.5 rounded-xl font-semibold bg-darkGreen-900 hover:bg-darkGreen-800 text-white transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{ isLoading ? 'Updating...' : 'Update' }}
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

input[type="color"] {
  -webkit-appearance: none;
  appearance: none;
  padding: 0;
}

input[type="color"]::-webkit-color-swatch-wrapper {
  padding: 4px;
}

input[type="color"]::-webkit-color-swatch {
  border: none;
  border-radius: 6px;
}
</style>