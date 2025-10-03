<script>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';

export default {
  components: { AppLayout, Head },
  props: {
    patient: {
      type: [Object, null],
      required: false,
      default: null,
      validator: (patient) => {
        if (patient === null) return true;
        return patient && typeof patient.patient_id === 'string' && Array.isArray(patient.charts);
      },
    },
  },
  data() {
    return {
      toothRecords: [],
      isLoading: false,
      error: null,
      creatingChart: false,
      searchQuery: '',
      rowsPerPage: 10,
      currentPage: 1,
      showViewModal: false,
      viewToothRecord: null,
      showEditModal: false,
      editToothRecord: null,
      toothMarks: {}, // { tooth_mark_id: { name: mark_name, color: mark_color } }
      healthyMarkId: null,
      selectedToothNumber: null,
      selectedSurface: null,
    };
  },
  computed: {
    breadcrumbs() {
      const crumbs = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Dental Charts', href: '/dental-charts' },
      ];
      return crumbs;
    },
    filteredToothRecords() {
      let filtered = this.toothRecords;
      if (this.searchQuery) {
        filtered = filtered.filter(r =>
          r.tooth_number.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
          r.condition.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
          r.last_treatment.toLowerCase().includes(this.searchQuery.toLowerCase())
        );
      }
      return filtered.sort((a, b) => a.tooth_number.localeCompare(b.tooth_number));
    },
    paginatedToothRecords() {
      const start = (this.currentPage - 1) * this.rowsPerPage;
      return this.filteredToothRecords.slice(start, start + this.rowsPerPage);
    },
    totalPages() {
      return Math.ceil(this.filteredToothRecords.length / this.rowsPerPage) || 1;
    },
  },
  mounted() {
    if (!this.patient || !this.patient.patient_id) {
      this.error = 'Invalid or missing patient data.';
      return;
    }
    if (this.patient.charts.length > 0) {
      this.fetchToothRecords();
    }
    this.fetchToothMarks();
  },
  methods: {
    async fetchToothMarks() {
      try {
        this.isLoading = true;
        const response = await axios.get('/tooth-marks/data');
        this.toothMarks = response.data.toothMarks.reduce((acc, mark) => {
          acc[mark.tooth_mark_id] = { name: mark.mark_name, color: mark.mark_color || '#00FF00' };
          if (mark.mark_name === 'Healthy') {
            this.healthyMarkId = mark.tooth_mark_id;
          }
          return acc;
        }, {});
        this.error = null;
      } catch (err) {
        this.toothMarks = {};
        this.healthyMarkId = null;
        this.error = err.response?.data?.error || 'Failed to fetch tooth marks.';
        console.error('Error fetching tooth marks:', err);
      } finally {
        this.isLoading = false;
      }
    },
    async fetchToothRecords() {
      if (!this.patient || !this.patient.patient_id) {
        this.error = 'Invalid or missing patient data.';
        this.isLoading = false;
        return;
      }
      try {
        this.isLoading = true;
        const response = await axios.get(`/dentalChart/${this.patient.patient_id}/tooth-records`);
        this.toothRecords = response.data.toothRecords.map(record => ({
          ...record,
          condition: record.surfaces && record.surfaces.length > 0 && record.surfaces.some(s => s.surface_status !== record.surfaces[0].surface_status)
            ? 'Mixed'
            : this.toothMarks[record.tooth_status]?.name || 'Healthy',
        }));
        this.error = null;
        console.log('Fetched tooth records:', this.toothRecords);
      } catch (err) {
        this.toothRecords = [];
        this.error = err.response?.status === 404
          ? 'No dental chart found for this patient.'
          : err.response?.data?.error || 'Failed to fetch tooth records.';
        console.error('Error fetching tooth records:', err);
      } finally {
        this.isLoading = false;
      }
    },
    async createDentalChart() {
      if (!this.patient || !this.patient.patient_id) {
        this.error = 'Invalid or missing patient data.';
        return;
      }
      if (this.creatingChart) return;
      if (!confirm('Are you sure you want to create a new dental chart?')) return;
      try {
        this.creatingChart = true;
        this.error = null;
        const response = await axios.post('/dental-charts', { patient_id: this.patient.patient_id });
        console.log('Chart creation response:', response.data);
        this.patient.charts.push({
          chart_id: response.data.chart.chart_id,
          created_at: response.data.chart.created_at,
          updated_at: response.data.chart.created_at,
        });
        await this.fetchToothRecords();
      } catch (err) {
        this.error = err.response?.data?.error || err.response?.data?.details || 'Failed to create dental chart.';
        console.error('Error creating dental chart:', err);
      } finally {
        this.creatingChart = false;
      }
    },
    async openEditModal(record) {
      try {
        this.isLoading = true;
        const response = await axios.get(`/tooth/${record.tooth_id}`);
        this.editToothRecord = {
          tooth_id: response.data.tooth.tooth_id,
          tooth_number: response.data.tooth.tooth_number,
          tooth_notes: response.data.tooth.tooth_notes || '',
          tooth_status: response.data.tooth.tooth_status || this.healthyMarkId || '',
          surfaces: response.data.surfaces.map(surface => ({
            surface_id: surface.surface_id,
            surface_type: surface.surface_type,
            surface_status: surface.surface_status || '',
            surface_notes: surface.surface_notes || '',
          })),
        };
        this.selectedToothNumber = record.tooth_number;
        this.selectedSurface = null;
        this.showEditModal = true;
        this.error = null;
      } catch (err) {
        this.error = err.response?.data?.error || 'Failed to load tooth data for editing.';
        console.error('Error loading tooth data:', err);
      } finally {
        this.isLoading = false;
      }
    },
    async saveEdit() {
      if (!this.editToothRecord) {
        this.error = 'No tooth data to save.';
        return;
      }
      try {
        this.isLoading = true;
        await axios.put(`/tooth/${this.editToothRecord.tooth_id}`, this.editToothRecord);
        this.showEditModal = false;
        this.editToothRecord = null;
        this.selectedToothNumber = null;
        this.selectedSurface = null;
        await this.fetchToothRecords();
        this.error = null;
      } catch (err) {
        this.error = err.response?.data?.error || 'Failed to update tooth record.';
        console.error('Error updating tooth record:', err);
      } finally {
        this.isLoading = false;
      }
    },
    closeEditModal() {
      this.showEditModal = false;
      this.editToothRecord = null;
      this.selectedToothNumber = null;
      this.selectedSurface = null;
    },
    prevPage() {
      if (this.currentPage > 1) this.currentPage--;
    },
    nextPage() {
      if (this.currentPage < this.totalPages) this.currentPage++;
    },
    openViewModal(record) {
      this.viewToothRecord = {
        ...record,
        surfaces: record.surfaces.map(surface => ({
          ...surface,
          surface_status: this.toothMarks[surface.surface_status]?.name || 'Healthy',
        })),
      };
      this.showViewModal = true;
    },
    closeViewModal() {
      this.showViewModal = false;
      this.viewToothRecord = null;
    },
    formatDateTime(dateTime) {
      return dateTime
        ? new Date(dateTime).toLocaleString('en-US', {
            month: 'long',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
          })
        : 'N/A';
    },
    getConditionColor(condition, toothStatus = null, surfaceStatus = null) {
      if (condition === 'Healthy') {
        return 'bg-green-100 text-green-600';
      }
      if (condition === 'Mixed') {
        return 'bg-purple-100 text-purple-800';
      }
      if (surfaceStatus && this.toothMarks[surfaceStatus]) {
        const markColor = this.toothMarks[surfaceStatus].color || '#00FF00';
        return `bg-[${markColor}]/10 text-[${markColor}]`;
      }
      if (toothStatus && this.toothMarks[toothStatus]) {
        const markColor = this.toothMarks[toothStatus].color || '#00FF00';
        return `bg-[${markColor}]/10 text-[${markColor}]`;
      }
      const markId = Object.keys(this.toothMarks).find(id => this.toothMarks[id].name === condition);
      const markColor = this.toothMarks[markId]?.color || '#00FF00';
      return `bg-[${markColor}]/10 text-[${markColor}]`;
    },
    getToothClass(toothNumber) {
      const record = this.toothRecords.find(r => r.tooth_number === toothNumber.toString());
      const baseClass = `p-2 border rounded text-center cursor-pointer hover:bg-gray-100 transition ${this.getConditionColor(record?.condition || 'Healthy', record?.tooth_status)}`;
      return this.selectedToothNumber === toothNumber.toString()
        ? `${baseClass} border-2 border-darkGreen-900`
        : baseClass;
    },
    openEditModalByToothNumber(toothNumber) {
      const record = this.toothRecords.find(r => r.tooth_number === toothNumber.toString());
      if (record) {
        this.openEditModal(record);
      } else {
        this.error = `No record found for tooth ${toothNumber}`;
      }
    },
    selectSurface(surface) {
      this.selectedSurface = surface.surface_id;
    },
  },
};
</script>

<template>
  <Head title="Dental Chart" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">
          Dental Chart
        </h1>
      </div>

      <div v-if="!patient || !this.patient.patient_id" class="text-center py-4 text-red-500">
        Invalid or missing patient data.
      </div>

      <div v-else-if="isLoading" class="text-center py-4 text-gray-500">
        Loading tooth records...
      </div>

      <div v-else-if="error" class="text-center py-4 text-red-500">
        {{ error }}
      </div>

      <div v-else-if="patient.charts.length === 0" class="bg-white rounded-lg shadow-md p-6 text-center">
        <p class="text-gray-500 text-lg mb-4">No dental chart found for this patient.</p>
        <button
          @click="createDentalChart"
          :disabled="creatingChart"
          class="bg-darkGreen-900 text-white px-6 py-2 rounded-lg hover:bg-darkGreen-800 transition-colors duration-200 disabled:opacity-50"
        >
          {{ creatingChart ? 'Creating...' : 'Create Dental Chart' }}
        </button>
      </div>

      <div v-else class="bg-white rounded-lg shadow-md p-6">
        <!-- Dental Chart Visual -->
        <div class="mb-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Dental Chart</h2>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <h3 class="text-md font-medium text-gray-700">Upper Arch</h3>
              <div class="grid grid-cols-8 gap-2 mt-2">
                <div
                  v-for="n in [18, 17, 16, 15, 14, 13, 12, 11, 21, 22, 23, 24, 25, 26, 27, 28]"
                  :key="n"
                  :class="getToothClass(n)"
                  @click="openEditModalByToothNumber(n)"
                >
                  {{ n }}
                </div>
              </div>
            </div>
            <div>
              <h3 class="text-md font-medium text-gray-700">Lower Arch</h3>
              <div class="grid grid-cols-8 gap-2 mt-2">
                <div
                  v-for="n in [48, 47, 46, 45, 44, 43, 42, 41, 31, 32, 33, 34, 35, 36, 37, 38]"
                  :key="n"
                  :class="getToothClass(n)"
                  @click="openEditModalByToothNumber(n)"
                >
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
              <tr v-for="record in paginatedToothRecords" :key="record.tooth_id" class="border-b last:border-b-0 hover:bg-gray-50">
                <td class="px-4 py-2">{{ record.tooth_number }}</td>
                <td class="px-4 py-2">
                  <span :class="`px-2 py-1 rounded-full text-xs font-medium ${getConditionColor(record.condition, record.tooth_status)}`">
                    {{ record.condition }}
                  </span>
                </td>
                <td class="px-4 py-2">{{ record.last_treatment }}</td>
                <td class="px-4 py-2">{{ formatDateTime(record.last_updated) }}</td>
                <td class="px-4 py-2 flex gap-2">
                  <button
                    @click="openViewModal(record)"
                    class="bg-gray-600 text-white px-3 py-1 rounded hover:bg-gray-700 transition text-sm"
                  >View</button>
                  <button
                    @click="openEditModal(record)"
                    class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition text-sm"
                  >Edit</button>
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
                <p :class="`text-sm ${getConditionColor(viewToothRecord.condition, viewToothRecord.tooth_status)}`">
                  {{ viewToothRecord.condition }}
                </p>
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
              <div v-if="viewToothRecord.surfaces && viewToothRecord.surfaces.length">
                <label class="block text-sm font-medium text-gray-700">Surfaces</label>
                <div v-for="surface in viewToothRecord.surfaces" :key="surface.surface_type" class="ml-2">
                  <p class="text-gray-900">
                    {{ surface.surface_type }}: {{ surface.surface_status }}
                    <span v-if="surface.surface_notes">({{ surface.surface_notes }})</span>
                  </p>
                </div>
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

        <!-- Edit Tooth Record Modal -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/20 backdrop-blur-sm">
          <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-lg mx-4">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-xl font-bold text-gray-900">Edit Tooth {{ editToothRecord?.tooth_number }}</h2>
              <button
                @click="closeEditModal"
                class="text-gray-400 hover:text-gray-600 text-2xl font-bold"
              >&times;</button>
            </div>
            <div v-if="editToothRecord" class="space-y-6">
              <!-- Tooth Diagram -->
              <div class="flex justify-center">
                <div class="relative w-40 h-40 bg-gray-100 rounded-lg border border-gray-300">
                  <!-- Occlusal/Incisal (Center) -->
                  <div
                    v-for="surface in editToothRecord.surfaces.filter(s => ['Occlusal', 'Incisal'].includes(s.surface_type))"
                    :key="surface.surface_id"
                    :class="[
                      'absolute top-1/3 left-1/3 w-1/3 h-1/3 border border-gray-400 rounded cursor-pointer hover:opacity-80',
                      getConditionColor(toothMarks[surface.surface_status]?.name || 'Healthy', null, surface.surface_status),
                      { 'ring-2 ring-darkGreen-900': selectedSurface === surface.surface_id }
                    ]"
                    :title="`${surface.surface_type}: ${toothMarks[surface.surface_status]?.name || 'Healthy'}${surface.surface_notes ? ' - ' + surface.surface_notes : ''}`"
                    @click="selectSurface(surface)"
                  >
                    <span class="text-xs font-medium absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                      {{ surface.surface_type.charAt(0) }}
                    </span>
                  </div>
                  <!-- Mesial (Left) -->
                  <div
                    v-for="surface in editToothRecord.surfaces.filter(s => s.surface_type === 'Mesial')"
                    :key="surface.surface_id"
                    :class="[
                      'absolute top-1/3 left-0 w-1/3 h-1/3 border border-gray-400 rounded-l cursor-pointer hover:opacity-80',
                      getConditionColor(toothMarks[surface.surface_status]?.name || 'Healthy', null, surface.surface_status),
                      { 'ring-2 ring-darkGreen-900': selectedSurface === surface.surface_id }
                    ]"
                    :title="`${surface.surface_type}: ${toothMarks[surface.surface_status]?.name || 'Healthy'}${surface.surface_notes ? ' - ' + surface.surface_notes : ''}`"
                    @click="selectSurface(surface)"
                  >
                    <span class="text-xs font-medium absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                      {{ surface.surface_type.charAt(0) }}
                    </span>
                  </div>
                  <!-- Distal (Right) -->
                  <div
                    v-for="surface in editToothRecord.surfaces.filter(s => s.surface_type === 'Distal')"
                    :key="surface.surface_id"
                    :class="[
                      'absolute top-1/3 right-0 w-1/3 h-1/3 border border-gray-400 rounded-r cursor-pointer hover:opacity-80',
                      getConditionColor(toothMarks[surface.surface_status]?.name || 'Healthy', null, surface.surface_status),
                      { 'ring-2 ring-darkGreen-900': selectedSurface === surface.surface_id }
                    ]"
                    :title="`${surface.surface_type}: ${toothMarks[surface.surface_status]?.name || 'Healthy'}${surface.surface_notes ? ' - ' + surface.surface_notes : ''}`"
                    @click="selectSurface(surface)"
                  >
                    <span class="text-xs font-medium absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                      {{ surface.surface_type.charAt(0) }}
                    </span>
                  </div>
                  <!-- Buccal/Facial (Top) -->
                  <div
                    v-for="surface in editToothRecord.surfaces.filter(s => ['Buccal', 'Facial'].includes(s.surface_type))"
                    :key="surface.surface_id"
                    :class="[
                      'absolute top-0 left-1/3 w-1/3 h-1/3 border border-gray-400 rounded-t cursor-pointer hover:opacity-80',
                      getConditionColor(toothMarks[surface.surface_status]?.name || 'Healthy', null, surface.surface_status),
                      { 'ring-2 ring-darkGreen-900': selectedSurface === surface.surface_id }
                    ]"
                    :title="`${surface.surface_type}: ${toothMarks[surface.surface_status]?.name || 'Healthy'}${surface.surface_notes ? ' - ' + surface.surface_notes : ''}`"
                    @click="selectSurface(surface)"
                  >
                    <span class="text-xs font-medium absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                      {{ surface.surface_type.charAt(0) }}
                    </span>
                  </div>
                  <!-- Lingual/Palatal (Bottom) -->
                  <div
                    v-for="surface in editToothRecord.surfaces.filter(s => ['Lingual', 'Palatal'].includes(s.surface_type))"
                    :key="surface.surface_id"
                    :class="[
                      'absolute bottom-0 left-1/3 w-1/3 h-1/3 border border-gray-400 rounded-b cursor-pointer hover:opacity-80',
                      getConditionColor(toothMarks[surface.surface_status]?.name || 'Healthy', null, surface.surface_status),
                      { 'ring-2 ring-darkGreen-900': selectedSurface === surface.surface_id }
                    ]"
                    :title="`${surface.surface_type}: ${toothMarks[surface.surface_status]?.name || 'Healthy'}${surface.surface_notes ? ' - ' + surface.surface_notes : ''}`"
                    @click="selectSurface(surface)"
                  >
                    <span class="text-xs font-medium absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                      {{ surface.surface_type.charAt(0) }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Surface Editor -->
              <div v-if="selectedSurface" class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700">
                    {{ editToothRecord.surfaces.find(s => s.surface_id === selectedSurface)?.surface_type }} Condition
                  </label>
                  <select
                    v-model="editToothRecord.surfaces.find(s => s.surface_id === selectedSurface).surface_status"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                  >
                    <option value="">Select Condition</option>
                    <option v-for="(mark, id) in toothMarks" :key="id" :value="id">{{ mark.name }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">
                    {{ editToothRecord.surfaces.find(s => s.surface_id === selectedSurface)?.surface_type }} Notes
                  </label>
                  <textarea
                    v-model="editToothRecord.surfaces.find(s => s.surface_id === selectedSurface).surface_notes"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                    rows="3"
                    :placeholder="`Enter notes for ${editToothRecord.surfaces.find(s => s.surface_id === selectedSurface)?.surface_type}`"
                  ></textarea>
                </div>
              </div>

              <!-- Tooth Condition -->
              <div>
                <label class="block text-sm font-medium text-gray-700">Tooth Condition</label>
                <select
                  v-model="editToothRecord.tooth_status"
                  class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                >
                  <option value="">Select Condition</option>
                  <option v-for="(mark, id) in toothMarks" :key="id" :value="id">{{ mark.name }}</option>
                </select>
              </div>

              <!-- Tooth Notes -->
              <div>
                <label class="block text-sm font-medium text-gray-700">Tooth Notes</label>
                <textarea
                  v-model="editToothRecord.tooth_notes"
                  class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                  rows="4"
                  placeholder="Enter tooth notes"
                ></textarea>
              </div>

              <!-- Buttons -->
              <div class="flex justify-end pt-4 gap-2">
                <button
                  @click="closeEditModal"
                  class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 transition-colors duration-200"
                >Cancel</button>
                <button
                  @click="saveEdit"
                  class="bg-darkGreen-900 text-white px-4 py-2 rounded hover:bg-darkGreen-800 transition-colors duration-200"
                >Save</button>
              </div>
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