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
        mark_color: '#000000',
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
        this.form = { mark_name: '', mark_color: '#000000' };
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
      this.form = { mark_name: '', mark_color: '#000000' };
      this.showCreateModal = true;
      this.error = null;
    },
    closeCreateModal() {
      this.showCreateModal = false;
      this.form = { mark_name: '', mark_color: '#000000' };
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
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">Tooth Marks</h1>
        <button
          @click="openCreateModal"
          class="bg-darkGreen-900 text-white px-6 py-2 rounded-lg hover:bg-darkGreen-800 transition-colors duration-200"
        >
          Add Tooth Mark
        </button>
      </div>

      <div v-if="isLoading" class="text-center py-4 text-gray-500">
        Loading tooth marks...
      </div>

      <div v-else-if="error" class="text-center py-4 text-red-500">
        {{ error }}
      </div>

      <div v-else-if="toothMarks.length === 0" class="bg-white rounded-lg shadow-md p-6 text-center">
        <p class="text-gray-500 text-lg mb-4">No tooth marks found.</p>
      </div>

      <div v-else class="bg-white rounded-lg shadow-md p-6">
        <div class="overflow-x-auto">
          <table class="min-w-full bg-white rounded-lg shadow overflow-hidden">
            <thead>
              <tr class="bg-darkGreen-900 text-white">
                <th class="px-4 py-2 text-left">Mark Name</th>
                <th class="px-4 py-2 text-left">Color</th>
                <th class="px-4 py-2 text-left">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="mark in toothMarks" :key="mark.tooth_mark_id" class="border-b last:border-b-0 hover:bg-gray-50">
                <td class="px-4 py-2">{{ mark.mark_name }}</td>
                <td class="px-4 py-2">
                  <div class="flex items-center gap-2">
                    <span
                      class="inline-block w-6 h-6 rounded"
                      :style="{ backgroundColor: mark.mark_color }"
                    ></span>
                    {{ mark.mark_color }}
                  </div>
                </td>
                <td class="px-4 py-2">
                  <button
                    @click="openEditModal(mark)"
                    class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition text-sm"
                  >Edit</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Create Tooth Mark Modal -->
      <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/20 backdrop-blur-sm">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-900">Create Tooth Mark</h2>
            <button
              @click="closeCreateModal"
              class="text-gray-400 hover:text-gray-600 text-2xl font-bold"
            >&times;</button>
          </div>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Mark Name</label>
              <input
                v-model="form.mark_name"
                type="text"
                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                placeholder="Enter mark name"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Color</label>
              <input
                v-model="form.mark_color"
                type="color"
                class="w-full h-10 border border-gray-300 rounded-lg p-1 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
              />
            </div>
            <div v-if="error" class="text-red-500 text-sm">{{ error }}</div>
            <div class="flex justify-end pt-4 gap-2">
              <button
                @click="closeCreateModal"
                class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 transition-colors duration-200"
              >Cancel</button>
              <button
                @click="createToothMark"
                class="bg-darkGreen-900 text-white px-4 py-2 rounded hover:bg-darkGreen-800 transition-colors duration-200"
                :disabled="isLoading"
              >
                {{ isLoading ? 'Creating...' : 'Create' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Edit Tooth Mark Modal -->
      <div v-if="showEditModal && editToothMark" class="fixed inset-0 z-50 flex items-center justify-center bg-black/20 backdrop-blur-sm">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-900">Edit Tooth Mark</h2>
            <button
              @click="closeEditModal"
              class="text-gray-400 hover:text-gray-600 text-2xl font-bold"
            >&times;</button>
          </div>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Mark Name</label>
              <input
                v-model="editToothMark.mark_name"
                type="text"
                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                placeholder="Enter mark name"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Color</label>
              <input
                v-model="editToothMark.mark_color"
                type="color"
                class="w-full h-10 border border-gray-300 rounded-lg p-1 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
              />
            </div>
            <div v-if="error" class="text-red-500 text-sm">{{ error }}</div>
            <div class="flex justify-end pt-4 gap-2">
              <button
                @click="closeEditModal"
                class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 transition-colors duration-200"
              >Cancel</button>
              <button
                @click="updateToothMark"
                class="bg-darkGreen-900 text-white px-4 py-2 rounded hover:bg-darkGreen-800 transition-colors duration-200"
                :disabled="isLoading"
              >
                {{ isLoading ? 'Updating...' : 'Update' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.bg-darkGreen-900 { background-color: #1e4f4f; }
.bg-darkGreen-800 { background-color: #1a4040; }
</style>