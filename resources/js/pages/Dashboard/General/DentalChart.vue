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
    },
  },
  data() {
    return {
      toothRecords: [],
      isLoading: false,
      error: null,
      creatingChart: false,
      toothMarks: {},
      healthyMarkId: null,
      selectedTooth: null,
      showEditModal: false,
      editForm: {
        tooth_status: null,
        tooth_notes: '',
        status_type: null,
        diagnosed_by: null,
        surfaces: [],
      },
      isSaving: false,
      statusTypes: ['treated_here', 'pre_existing', 'observed'],
      dentists: [],
    };
  },
  computed: {
    authUser() {
      return this.$page.props.auth?.user;
    },
    breadcrumbs() {
      return [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Patients', href: '/patients' },
        { title: 'Dental Chart', href: '#' },
      ];
    },
    markedTeeth() {
      return this.toothRecords
        .filter(tooth => {
          const hasMarkedSurfaces = tooth.surfaces?.some(
            s => s.surface_status && this.toothMarks[s.surface_status]?.name !== 'Healthy'
          );
          const hasMarkedTooth = tooth.tooth_status && this.toothMarks[tooth.tooth_status]?.name !== 'Healthy';
          return hasMarkedSurfaces || hasMarkedTooth;
        })
        .sort((a, b) => {
          const dateA = new Date(a.last_updated || a.updated_at);
          const dateB = new Date(b.last_updated || b.updated_at);
          return dateB - dateA;
        });
    },
    availableSurfaces() {
      const toothNum = parseInt(this.selectedTooth?.tooth_number);
      const anteriorTeeth = [
        11, 12, 13, 21, 22, 23, 31, 32, 33, 41, 42, 43,
        51, 52, 53, 61, 62, 63, 71, 72, 73, 81, 82, 83,
      ];
      const maxillaryTeeth = [
        11, 12, 13, 14, 15, 16, 17, 18, 21, 22, 23, 24, 25, 26, 27, 28,
        51, 52, 53, 54, 55, 61, 62, 63, 64, 65,
      ];
      if (anteriorTeeth.includes(toothNum)) {
        return maxillaryTeeth.includes(toothNum)
          ? ['Mesial', 'Distal', 'Labial', 'Palatal', 'Incisal']
          : ['Mesial', 'Distal', 'Labial', 'Lingual', 'Incisal'];
      }
      return maxillaryTeeth.includes(toothNum)
        ? ['Mesial', 'Distal', 'Buccal', 'Palatal', 'Occlusal']
        : ['Mesial', 'Distal', 'Buccal', 'Lingual', 'Occlusal'];
    },
    currentChartId() {
      return this.patient?.charts?.[0]?.chart_id || null;
    },
    isPatient() {
      return this.authUser?.user_type === 'Patient';
    },
    isDentist() {
      return this.authUser?.user_type === 'Dentist';
    },
    pageTitle() {
      if (!this.patient) return 'Dental Chart';
      if (this.isPatient) {
        return 'My Dental Chart';
      }
      return `Dental Chart - ${this.patient.first_name} ${this.patient.last_name}`;
    },
    filteredDentists() {
      if (this.isDentist) {
        return this.dentists.filter(dentist =>
          dentist.user_id === this.authUser.user_id ||
          dentist.appointments?.some(appt => appt.patient_id === this.patient?.patient_id)
        );
      } else if (this.authUser?.user_type === 'Receptionist') {
        return this.dentists.filter(dentist => dentist.user_branch === this.authUser.user_branch);
      }
      return this.dentists;
    },
    canSave() {
      if (!this.editForm.status_type || !this.statusTypes.includes(this.editForm.status_type)) {
        console.log('Tooth status_type is missing or invalid:', this.editForm.status_type);
        return false;
      }
      if (this.editForm.status_type === 'treated_here' && this.isDentist && !this.editForm.diagnosed_by) {
        console.log('Tooth diagnosed_by is missing for treated_here');
        return false;
      }
      for (const surface of this.editForm.surfaces) {
        if (!surface.status_type || !this.statusTypes.includes(surface.status_type)) {
          console.log(`Surface ${surface.surface_type} has invalid status_type:`, surface.status_type);
          return false;
        }
        if (surface.status_type === 'treated_here' && this.isDentist && !surface.diagnosed_by) {
          console.log(`Surface ${surface.surface_type} is missing diagnosed_by for treated_here`);
          return false;
        }
      }
      return true;
    },
  },
  mounted() {
    if (!this.patient?.patient_id) {
      this.error = 'Invalid or missing patient data.';
      return;
    }
    this.fetchToothMarks();
    if (this.patient.charts?.length > 0) {
      this.fetchToothRecords();
    }
    this.fetchDentists();
  },
  methods: {
    async fetchToothMarks() {
      try {
        const response = await axios.get('/tooth-marks/data');
        this.toothMarks = response.data.toothMarks.reduce((acc, mark) => {
          acc[mark.tooth_mark_id] = {
            name: mark.mark_name,
            color: mark.mark_color || '#10B981',
          };
          if (mark.mark_name === 'Healthy') {
            this.healthyMarkId = mark.tooth_mark_id;
            console.log('Healthy mark ID set:', this.healthyMarkId);
          }
          return acc;
        }, {});
        if (!this.healthyMarkId) {
          console.warn('Healthy mark not found in toothMarks');
          this.error = 'Healthy mark not found. Please ensure tooth marks are properly configured.';
        }
      } catch (err) {
        console.error('Error fetching tooth marks:', err);
        this.error = 'Failed to load tooth mark data.';
      }
    },
    async fetchToothRecords() {
      try {
        this.isLoading = true;
        const params = {};
        const urlParams = new URLSearchParams(window.location.search);
        const patientIdFromUrl = urlParams.get('patient_id');
        if (patientIdFromUrl) {
          params.patient_id = patientIdFromUrl;
        } else if (this.patient?.patient_id) {
          params.patient_id = this.patient.patient_id;
        }
        const response = await axios.get('/dentalChart/tooth-records', { params });
        this.toothRecords = response.data.toothRecords;
        this.error = null;
        console.log('Fetched tooth records:', this.toothRecords);
      } catch (err) {
        this.toothRecords = [];
        this.error =
          err.response?.status === 404
            ? 'No dental chart found for this patient.'
            : 'Failed to fetch tooth records.';
        console.error('Error fetching tooth records:', err.response?.data);
      } finally {
        this.isLoading = false;
      }
    },
    async fetchDentists() {
      try {
        const params = {};
        if (this.isPatient) {
          params.patient_id = this.patient?.patient_id;
        } else if (this.authUser?.user_type === 'Receptionist') {
          params.branch_id = this.authUser.user_branch;
          console.log('Fetching dentists for branch:', params.branch_id);
        }
        const response = await axios.get(route('owner.dentists.data'), { params });
        this.dentists = response.data.dentists;
        console.log('Fetched dentists:', this.dentists);
      } catch (err) {
        console.error('Error fetching dentists:', err.response?.data || err);
        this.error = 'Failed to load dentists list.';
      }
    },
    getUserName(userId) {
      if (!userId) {
        return 'N/A';
      }
      if (userId === this.authUser?.user_id) {
        return this.authUser?.first_name && this.authUser?.last_name
          ? `${this.authUser.first_name} ${this.authUser.last_name}`
          : 'Current User';
      }
      const dentist = this.dentists.find(d => d.user_id === userId);
      return dentist ? `${dentist.first_name} ${dentist.last_name}` : 'Unknown Dentist';
    },
    getToothRecord(toothNumber) {
      const record = this.toothRecords.find(r => r.tooth_number === toothNumber.toString());
      return record;
    },
    getSurfaceColor(tooth, surfaceType) {
      if (!tooth?.surfaces) {
        return '#FFFFFF';
      }
      const surface = tooth.surfaces.find(s => s.surface_type === surfaceType);
      if (!surface?.surface_status) {
        return '#FFFFFF';
      }
      return this.toothMarks[surface.surface_status]?.color || '#FFFFFF';
    },
    getToothColor(toothNumber) {
      const tooth = this.getToothRecord(toothNumber);
      if (!tooth?.tooth_status) {
        return '#FFFFFF';
      }
      return this.toothMarks[tooth.tooth_status]?.color || '#FFFFFF';
    },
    selectTooth(toothNumber) {
      const tooth = this.getToothRecord(toothNumber);
      if (!tooth) {
        this.error = `Tooth ${toothNumber} not found. Please ensure the dental chart is properly initialized.`;
        console.error(`No tooth record found for tooth number: ${toothNumber}`);
        this.selectedTooth = null;
        return;
      }
      this.selectedTooth = { ...tooth };
      this.error = null;
      console.log('Selected tooth:', this.selectedTooth);
    },
    openEditModal() {
      if (!this.selectedTooth || this.isPatient) return;

      const surfaces = this.availableSurfaces.map(surfaceType => {
        const existingSurface = this.selectedTooth.surfaces?.find(s => s.surface_type === surfaceType) || {};
        return {
          surface_id: existingSurface.surface_id || null,
          surface_type: surfaceType,
          surface_status: existingSurface.surface_status || this.healthyMarkId,
          surface_notes: existingSurface.surface_notes || '',
          status_type: existingSurface.status_type || 'observed',
          diagnosed_by: existingSurface.diagnosed_by || null,
        };
      });

      if (surfaces.length === 0) {
        this.error = `No valid surfaces found for tooth ${this.selectedTooth.tooth_number}.`;
        this.selectedTooth = null;
        return;
      }

      this.editForm = {
        tooth_status: this.selectedTooth.tooth_status || this.healthyMarkId,
        tooth_notes: this.selectedTooth.tooth_notes || '',
        status_type: this.selectedTooth.status_type || 'observed',
        diagnosed_by: this.selectedTooth.diagnosed_by || null,
        surfaces,
      };

      this.showEditModal = true;
      this.error = null;
      console.log('Edit form initialized:', this.editForm);
    },
    closeEditModal() {
      this.showEditModal = false;
      this.editForm = {
        tooth_status: null,
        tooth_notes: '',
        status_type: null,
        diagnosed_by: null,
        surfaces: [],
      };
      this.error = null;
      this.selectedTooth = null;
    },
    async saveTooth() {
      if (this.isPatient || !this.canSave) return;

      try {
        this.isSaving = true;
        this.error = null;

        const formData = {
          tooth_status: this.editForm.tooth_status || this.healthyMarkId,
          tooth_notes: this.editForm.tooth_notes || '',
          status_type: this.editForm.status_type || 'observed',
          diagnosed_by:
            this.editForm.status_type === 'treated_here' ? this.editForm.diagnosed_by : null,
          surfaces: this.editForm.surfaces.map(surface => ({
            surface_id: surface.surface_id || null,
            surface_type: surface.surface_type,
            surface_status: surface.surface_status || this.healthyMarkId,
            surface_notes: surface.surface_notes || '',
            status_type: surface.status_type || 'observed',
            diagnosed_by:
              surface.status_type === 'treated_here' ? surface.diagnosed_by : null,
          })),
        };

        console.log('Saving tooth with formData:', JSON.stringify(formData, null, 2));

        await axios.put(`/tooth/${this.selectedTooth.tooth_id}`, formData);

        this.showEditModal = false;
        await this.fetchToothRecords();

        const updatedTooth = this.getToothRecord(this.selectedTooth.tooth_number);
        if (updatedTooth) {
          this.selectedTooth = { ...updatedTooth };
        }
      } catch (err) {
        const errorMessage =
          err.response?.data?.errors
            ? Object.values(err.response.data.errors).flat().join('; ')
            : err.response?.data?.error || 'Failed to update tooth.';
        this.error = errorMessage;
        console.error('Error saving tooth:', err.response?.data);
      } finally {
        this.isSaving = false;
      }
    },
    formatDate(date) {
      if (!date) return 'N/A';
      return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
      });
    },
    formatStatusType(status) {
      if (!status) return 'N/A';
      return status
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
    },
    onSurfaceStatusTypeChange(index) {
      console.log(`Surface ${this.editForm.surfaces[index].surface_type} status_type changed to:`, this.editForm.surfaces[index].status_type);
      if (!this.statusTypes.includes(this.editForm.surfaces[index].status_type)) {
        this.editForm.surfaces[index].status_type = 'observed';
      }
      if (this.editForm.surfaces[index].status_type !== 'treated_here') {
        this.editForm.surfaces[index].diagnosed_by = null;
      }
    },
  },
};
</script>
<template>
  <Head title="Dental Chart" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-6">
      <div class="bg-white rounded-lg shadow-sm p-6">
        <h1 class="text-2xl font-bold text-gray-900">Dental Chart</h1>
        <p v-if="!isPatient && patient" class="text-gray-600 mt-1">
          Patient ID: {{ patient.patient_id }}
        </p>
      </div>

      <div v-if="error && patient?.charts?.length === 0" class="bg-white rounded-lg shadow-sm p-8 text-center">
        <p class="text-gray-600 mb-4">{{ error }}</p>
        <button v-if="!isPatient" @click="createDentalChart" :disabled="creatingChart"
          class="bg-teal-700 text-white px-6 py-2 rounded-lg hover:bg-teal-800 transition disabled:opacity-50">
          {{ creatingChart ? 'Creating...' : 'Create Dental Chart' }}
        </button>
      </div>

      <div v-else-if="isLoading" class="bg-white rounded-lg shadow-sm p-12 text-center">
        <div class="text-gray-500">Loading dental chart...</div>
      </div>

      <div v-else class="grid grid-cols-1 xl:grid-cols-4 gap-6">
        <div class="xl:col-span-3 bg-white rounded-lg shadow-sm p-6 overflow-x-auto">
          <h2 class="text-lg font-semibold text-gray-900 mb-6">Dental Chart</h2>

          <div class="min-w-max">
            <!-- Upper Temporary Teeth -->
            <div class="mb-6 border-b border-gray-200 pb-4">
              <h3 class="text-sm font-semibold text-gray-800 mb-2">Upper Temporary Teeth</h3>
              <div class="flex items-start">
                <div class="w-28 flex items-center h-6"><span class="text-xs text-gray-600 font-medium">STATUS</span>
                </div>
                <div class="flex-1 flex justify-center">
                  <div class="flex gap-0">
                    <div v-for="n in [55, 54, 53, 52, 51]" :key="`st1-${n}`" class="w-11 h-6 border border-gray-300"
                      :style="{ backgroundColor: getToothColor(n) }"></div>
                    <div class="w-2"></div>
                    <div v-for="n in [61, 62, 63, 64, 65]" :key="`st2-${n}`" class="w-11 h-6 border border-gray-300"
                      :style="{ backgroundColor: getToothColor(n) }"></div>
                  </div>
                </div>
                <div class="w-28 flex items-center justify-end h-6"><span
                    class="text-xs text-gray-600 font-medium">LEFT</span></div>
              </div>
              <div class="flex items-start">
                <div class="w-28 flex items-center h-8"><span class="text-xs text-gray-600 font-medium">RIGHT</span>
                </div>
                <div class="flex-1 flex justify-center">
                  <div class="flex gap-0">
                    <div v-for="n in [55, 54, 53, 52, 51]" :key="`n1-${n}`"
                      class="w-11 h-8 text-center flex items-center justify-center text-xs font-semibold text-gray-700">
                      {{ n }}</div>
                    <div class="w-2"></div>
                    <div v-for="n in [61, 62, 63, 64, 65]" :key="`n2-${n}`"
                      class="w-11 h-8 text-center flex items-center justify-center text-xs font-semibold text-gray-700">
                      {{ n }}</div>
                  </div>
                </div>
                <div class="w-28"></div>
              </div>
              <div class="flex items-start">
                <div class="w-28"></div>
                <div class="flex-1 flex justify-center">
                  <div class="flex gap-0 items-center">
                    <div v-for="n in [55, 54, 53, 52, 51]" :key="`t1-${n}`"
                      class="w-11 h-14 flex items-center justify-center" @click="selectTooth(n)">
                      <div class="relative w-11 h-11 cursor-pointer hover:opacity-80 transition">
                        <div class="absolute top-0 left-1/4 w-1/2 h-1/4 border-b border-gray-400"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Labial') || getSurfaceColor(getToothRecord(n), 'Buccal') }">
                        </div>
                        <div class="absolute top-1/4 left-0 w-1/4 h-1/2 border-r border-gray-400"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Mesial') }"></div>
                        <div class="absolute top-1/4 left-1/4 w-1/2 h-1/2 flex items-center justify-center"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Incisal') || getSurfaceColor(getToothRecord(n), 'Occlusal') }">
                          <div class="w-4 h-4 rounded-full border-2 border-gray-800"
                            :style="{ backgroundColor: getToothColor(n) }"></div>
                        </div>
                        <div class="absolute top-1/4 right-0 w-1/4 h-1/2 border-l border-gray-400"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Distal') }"></div>
                        <div class="absolute bottom-0 left-1/4 w-1/2 h-1/4 border-t border-gray-400"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Palatal') || getSurfaceColor(getToothRecord(n), 'Lingual') }">
                        </div>
                      </div>
                    </div>
                    <div class="w-2"></div>
                    <div v-for="n in [61, 62, 63, 64, 65]" :key="`t2-${n}`"
                      class="w-11 h-14 flex items-center justify-center" @click="selectTooth(n)">
                      <div class="relative w-11 h-11 cursor-pointer hover:opacity-80 transition">
                        <div class="absolute top-0 left-1/4 w-1/2 h-1/4 border-b border-gray-400"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Labial') || getSurfaceColor(getToothRecord(n), 'Buccal') }">
                        </div>
                        <div class="absolute top-1/4 left-0 w-1/4 h-1/2 border-r border-gray-400"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Mesial') }"></div>
                        <div class="absolute top-1/4 left-1/4 w-1/2 h-1/2 flex items-center justify-center"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Incisal') || getSurfaceColor(getToothRecord(n), 'Occlusal') }">
                          <div class="w-4 h-4 rounded-full border-2 border-gray-800"
                            :style="{ backgroundColor: getToothColor(n) }"></div>
                        </div>
                        <div class="absolute top-1/4 right-0 w-1/4 h-1/2 border-l border-gray-400"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Distal') }"></div>
                        <div class="absolute bottom-0 left-1/4 w-1/2 h-1/4 border-t border-gray-400"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Palatal') || getSurfaceColor(getToothRecord(n), 'Lingual') }">
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="w-28"></div>
              </div>
            </div>

            <!-- Upper Permanent Teeth -->
            <div class="mb-6 border-b border-gray-200 pb-4">
              <h3 class="text-sm font-semibold text-gray-800 mb-2">Upper Permanent Teeth</h3>
              <div class="flex items-start">
                <div class="w-28"></div>
                <div class="flex-1 flex justify-center">
                  <div class="flex gap-0 items-center">
                    <div v-for="n in [18, 17, 16, 15, 14, 13, 12, 11]" :key="`p1-${n}`"
                      class="w-11 h-14 flex items-center justify-center" @click="selectTooth(n)">
                      <div class="relative w-11 h-11 cursor-pointer hover:opacity-80 transition">
                        <div class="absolute top-0 left-1/4 w-1/2 h-1/4 border-b border-gray-400"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Labial') || getSurfaceColor(getToothRecord(n), 'Buccal') }">
                        </div>
                        <div class="absolute top-1/4 left-0 w-1/4 h-1/2 border-r border-gray-400"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Mesial') }"></div>
                        <div class="absolute top-1/4 left-1/4 w-1/2 h-1/2 flex items-center justify-center"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Incisal') || getSurfaceColor(getToothRecord(n), 'Occlusal') }">
                          <div class="w-4 h-4 rounded-full border-2 border-gray-800"
                            :style="{ backgroundColor: getToothColor(n) }"></div>
                        </div>
                        <div class="absolute top-1/4 right-0 w-1/4 h-1/2 border-l border-gray-400"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Distal') }"></div>
                        <div class="absolute bottom-0 left-1/4 w-1/2 h-1/4 border-t border-gray-400"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Palatal') || getSurfaceColor(getToothRecord(n), 'Lingual') }">
                        </div>
                      </div>
                    </div>
                    <div class="w-2"></div>
                    <div v-for="n in [21, 22, 23, 24, 25, 26, 27, 28]" :key="`p2-${n}`"
                      class="w-11 h-14 flex items-center justify-center" @click="selectTooth(n)">
                      <div class="relative w-11 h-11 cursor-pointer hover:opacity-80 transition">
                        <div class="absolute top-0 left-1/4 w-1/2 h-1/4 border-b border-gray-400"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Labial') || getSurfaceColor(getToothRecord(n), 'Buccal') }">
                        </div>
                        <div class="absolute top-1/4 left-0 w-1/4 h-1/2 border-r border-gray-400"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Mesial') }"></div>
                        <div class="absolute top-1/4 left-1/4 w-1/2 h-1/2 flex items-center justify-center"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Incisal') || getSurfaceColor(getToothRecord(n), 'Occlusal') }">
                          <div class="w-4 h-4 rounded-full border-2 border-gray-800"
                            :style="{ backgroundColor: getToothColor(n) }"></div>
                        </div>
                        <div class="absolute top-1/4 right-0 w-1/4 h-1/2 border-l border-gray-400"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Distal') }"></div>
                        <div class="absolute bottom-0 left-1/4 w-1/2 h-1/4 border-t border-gray-400"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Palatal') || getSurfaceColor(getToothRecord(n), 'Lingual') }">
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="w-28"></div>
              </div>
              <div class="flex items-start">
                <div class="w-28"></div>
                <div class="flex-1 flex justify-center">
                  <div class="flex gap-0">
                    <div v-for="n in [18, 17, 16, 15, 14, 13, 12, 11]" :key="`pn1-${n}`"
                      class="w-11 h-8 text-center flex items-center justify-center text-xs font-semibold text-gray-700">
                      {{ n }}</div>
                    <div class="w-2"></div>
                    <div v-for="n in [21, 22, 23, 24, 25, 26, 27, 28]" :key="`pn2-${n}`"
                      class="w-11 h-8 text-center flex items-center justify-center text-xs font-semibold text-gray-700">
                      {{ n }}</div>
                  </div>
                </div>
                <div class="w-28"></div>
              </div>
              <div class="flex items-start">
                <div class="w-28"></div>
                <div class="flex-1 flex justify-center">
                  <div class="flex gap-0">
                    <div v-for="n in [18, 17, 16, 15, 14, 13, 12, 11]" :key="`ps1-${n}`"
                      class="w-11 h-6 border border-gray-300" :style="{ backgroundColor: getToothColor(n) }"></div>
                    <div class="w-2"></div>
                    <div v-for="n in [21, 22, 23, 24, 25, 26, 27, 28]" :key="`ps2-${n}`"
                      class="w-11 h-6 border border-gray-300" :style="{ backgroundColor: getToothColor(n) }"></div>
                  </div>
                </div>
                <div class="w-28"></div>
              </div>
            </div>

            <!-- Divider -->
            <div class="flex items-center my-6">
              <div class="w-28"></div>
              <div class="flex-1 border-t-2 border-gray-800 mx-2"></div>
              <div class="w-28"></div>
            </div>

            <!-- Lower Permanent Teeth -->
            <div class="mb-6 border-b border-gray-200 pb-4">
              <h3 class="text-sm font-semibold text-gray-800 mb-2">Lower Permanent Teeth</h3>
              <div class="flex items-start">
                <div class="w-28"></div>
                <div class="flex-1 flex justify-center">
                  <div class="flex gap-0">
                    <div v-for="n in [48, 47, 46, 45, 44, 43, 42, 41]" :key="`ls1-${n}`"
                      class="w-11 h-6 border border-gray-300" :style="{ backgroundColor: getToothColor(n) }"></div>
                    <div class="w-2"></div>
                    <div v-for="n in [31, 32, 33, 34, 35, 36, 37, 38]" :key="`ls2-${n}`"
                      class="w-11 h-6 border border-gray-300" :style="{ backgroundColor: getToothColor(n) }"></div>
                  </div>
                </div>
                <div class="w-28"></div>
              </div>
              <div class="flex items-start">
                <div class="w-28"></div>
                <div class="flex-1 flex justify-center">
                  <div class="flex gap-0">
                    <div v-for="n in [48, 47, 46, 45, 44, 43, 42, 41]" :key="`ln1-${n}`"
                      class="w-11 h-8 text-center flex items-center justify-center text-xs font-semibold text-gray-700">
                      {{ n }}</div>
                    <div class="w-2"></div>
                    <div v-for="n in [31, 32, 33, 34, 35, 36, 37, 38]" :key="`ln2-${n}`"
                      class="w-11 h-8 text-center flex items-center justify-center text-xs font-semibold text-gray-700">
                      {{ n }}</div>
                  </div>
                </div>
                <div class="w-28"></div>
              </div>
              <div class="flex items-start">
                <div class="w-28"></div>
                <div class="flex-1 flex justify-center">
                  <div class="flex gap-0 items-center">
                    <div v-for="n in [48, 47, 46, 45, 44, 43, 42, 41]" :key="`lp1-${n}`"
                      class="w-11 h-14 flex items-center justify-center" @click="selectTooth(n)">
                      <div class="relative w-11 h-11 cursor-pointer hover:opacity-80 transition">
                        <div class="absolute top-0 left-1/4 w-1/2 h-1/4 border-b border-gray-400"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Lingual') || getSurfaceColor(getToothRecord(n), 'Palatal') }">
                        </div>
                        <div class="absolute top-1/4 left-0 w-1/4 h-1/2 border-r border-gray-400"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Mesial') }"></div>
                        <div class="absolute top-1/4 left-1/4 w-1/2 h-1/2 flex items-center justify-center"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Incisal') || getSurfaceColor(getToothRecord(n), 'Occlusal') }">
                          <div class="w-4 h-4 rounded-full border-2 border-gray-800"
                            :style="{ backgroundColor: getToothColor(n) }"></div>
                        </div>
                        <div class="absolute top-1/4 right-0 w-1/4 h-1/2 border-l border-gray-400"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Distal') }"></div>
                        <div class="absolute bottom-0 left-1/4 w-1/2 h-1/4 border-t border-gray-400"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Labial') || getSurfaceColor(getToothRecord(n), 'Buccal') }">
                        </div>
                      </div>
                    </div>
                    <div class="w-2"></div>
                    <div v-for="n in [31, 32, 33, 34, 35, 36, 37, 38]" :key="`lp2-${n}`"
                      class="w-11 h-14 flex items-center justify-center" @click="selectTooth(n)">
                      <div class="relative w-11 h-11 cursor-pointer hover:opacity-80 transition">
                        <div class="absolute top-0 left-1/4 w-1/2 h-1/4 border-b border-gray-400"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Lingual') || getSurfaceColor(getToothRecord(n), 'Palatal') }">
                        </div>
                        <div class="absolute top-1/4 left-0 w-1/4 h-1/2 border-r border-gray-400"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Mesial') }"></div>
                        <div class="absolute top-1/4 left-1/4 w-1/2 h-1/2 flex items-center justify-center"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Incisal') || getSurfaceColor(getToothRecord(n), 'Occlusal') }">
                          <div class="w-4 h-4 rounded-full border-2 border-gray-800"
                            :style="{ backgroundColor: getToothColor(n) }"></div>
                        </div>
                        <div class="absolute top-1/4 right-0 w-1/4 h-1/2 border-l border-gray-400"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Distal') }"></div>
                        <div class="absolute bottom-0 left-1/4 w-1/2 h-1/4 border-t border-gray-400"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Labial') || getSurfaceColor(getToothRecord(n), 'Buccal') }">
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="w-28"></div>
              </div>
            </div>

            <!-- Lower Temporary Teeth -->
            <div class="mb-2">
              <h3 class="text-sm font-semibold text-gray-800 mb-2">Lower Temporary Teeth</h3>
              <div class="flex items-start">
                <div class="w-28"></div>
                <div class="flex-1 flex justify-center">
                  <div class="flex gap-0 items-center">
                    <div v-for="n in [85, 84, 83, 82, 81]" :key="`lt1-${n}`"
                      class="w-11 h-14 flex items-center justify-center" @click="selectTooth(n)">
                      <div class="relative w-11 h-11 cursor-pointer hover:opacity-80 transition">
                        <div class="absolute top-0 left-1/4 w-1/2 h-1/4 border-b border-gray-400"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Lingual') || getSurfaceColor(getToothRecord(n), 'Palatal') }">
                        </div>
                        <div class="absolute top-1/4 left-0 w-1/4 h-1/2 border-r border-gray-400"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Mesial') }"></div>
                        <div class="absolute top-1/4 left-1/4 w-1/2 h-1/2 flex items-center justify-center"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Incisal') || getSurfaceColor(getToothRecord(n), 'Occlusal') }">
                          <div class="w-4 h-4 rounded-full border-2 border-gray-800"
                            :style="{ backgroundColor: getToothColor(n) }"></div>
                        </div>
                        <div class="absolute top-1/4 right-0 w-1/4 h-1/2 border-l border-gray-400"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Distal') }"></div>
                        <div class="absolute bottom-0 left-1/4 w-1/2 h-1/4 border-t border-gray-400"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Labial') || getSurfaceColor(getToothRecord(n), 'Buccal') }">
                        </div>
                      </div>
                    </div>
                    <div class="w-2"></div>
                    <div v-for="n in [71, 72, 73, 74, 75]" :key="`lt2-${n}`"
                      class="w-11 h-14 flex items-center justify-center" @click="selectTooth(n)">
                      <div class="relative w-11 h-11 cursor-pointer hover:opacity-80 transition">
                        <div class="absolute top-0 left-1/4 w-1/2 h-1/4 border-b border-gray-400"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Lingual') || getSurfaceColor(getToothRecord(n), 'Palatal') }">
                        </div>
                        <div class="absolute top-1/4 left-0 w-1/4 h-1/2 border-r border-gray-400"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Mesial') }"></div>
                        <div class="absolute top-1/4 left-1/4 w-1/2 h-1/2 flex items-center justify-center"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Incisal') || getSurfaceColor(getToothRecord(n), 'Occlusal') }">
                          <div class="w-4 h-4 rounded-full border-2 border-gray-800"
                            :style="{ backgroundColor: getToothColor(n) }"></div>
                        </div>
                        <div class="absolute top-1/4 right-0 w-1/4 h-1/2 border-l border-gray-400"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Distal') }"></div>
                        <div class="absolute bottom-0 left-1/4 w-1/2 h-1/4 border-t border-gray-400"
                          :style="{ backgroundColor: getSurfaceColor(getToothRecord(n), 'Labial') || getSurfaceColor(getToothRecord(n), 'Buccal') }">
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="w-28"></div>
              </div>
              <div class="flex items-start">
                <div class="w-28"></div>
                <div class="flex-1 flex justify-center">
                  <div class="flex gap-0">
                    <div v-for="n in [85, 84, 83, 82, 81]" :key="`ltn1-${n}`"
                      class="w-11 h-8 text-center flex items-center justify-center text-xs font-semibold text-gray-700">
                      {{ n }}</div>
                    <div class="w-2"></div>
                    <div v-for="n in [71, 72, 73, 74, 75]" :key="`ltn2-${n}`"
                      class="w-11 h-8 text-center flex items-center justify-center text-xs font-semibold text-gray-700">
                      {{ n }}</div>
                  </div>
                </div>
                <div class="w-28"></div>
              </div>
              <div class="flex items-start">
                <div class="w-28 flex items-center h-6"><span class="text-xs text-gray-600 font-medium">STATUS
                    RIGHT</span></div>
                <div class="flex-1 flex justify-center">
                  <div class="flex gap-0">
                    <div v-for="n in [85, 84, 83, 82, 81]" :key="`lts1-${n}`" class="w-11 h-6 border border-gray-300"
                      :style="{ backgroundColor: getToothColor(n) }"></div>
                    <div class="w-2"></div>
                    <div v-for="n in [71, 72, 73, 74, 75]" :key="`lts2-${n}`" class="w-11 h-6 border border-gray-300"
                      :style="{ backgroundColor: getToothColor(n) }"></div>
                  </div>
                </div>
                <div class="w-28 flex items-center justify-end h-6"><span
                    class="text-xs text-gray-600 font-medium">LEFT</span></div>
              </div>
            </div>
          </div>

          <!-- Legend -->
          <div class="mt-8 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Legend</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
              <div v-for="(mark, id) in toothMarks" :key="id" class="flex items-center gap-2">
                <div class="w-5 h-5 rounded border border-gray-300 flex-shrink-0"
                  :style="{ backgroundColor: mark.color }"></div>
                <span class="text-sm text-gray-700">{{ mark.name }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="xl:col-span-1 space-y-6">
          <!-- Tooth Details -->
          <div v-if="selectedTooth" class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-gray-900">Tooth #{{ selectedTooth.tooth_number }}</h3>
              <div class="flex items-center gap-2">
                <button v-if="!isPatient" @click="openEditModal"
                  class="px-3 py-1.5 bg-teal-700 text-white text-sm rounded hover:bg-teal-800 transition">
                  Edit
                </button>
                <button @click="closeEditModal"
                  class="px-3 py-1.5 bg-gray-200 text-gray-700 text-sm rounded hover:bg-gray-300 transition">
                  Close
                </button>
              </div>
            </div>
            <div class="space-y-3 text-sm">
              <div>
                <span class="font-medium text-gray-700">Overall Condition:</span>
                <p class="text-gray-900 mt-1">
                  {{ selectedTooth.tooth_status ? (toothMarks[selectedTooth.tooth_status]?.name || 'Not Assessed') :
                  'Not Assessed' }}
                </p>
              </div>
              <div>
                <span class="font-medium text-gray-700">Status Type:</span>
                <p class="text-gray-900 mt-1">{{ formatStatusType(selectedTooth.status_type) }}</p>
              </div>
              <div>
                <span class="font-medium text-gray-700">Diagnosed By:</span>
                <p class="text-gray-900 mt-1">{{ getUserName(selectedTooth.diagnosed_by) || 'Not Assigned' }}</p>
              </div>
              <div>
                <span class="font-medium text-gray-700">Last Updated:</span>
                <p class="text-gray-900 mt-1">{{ selectedTooth.last_updated ? formatDate(selectedTooth.last_updated) :
                  'No updates' }}</p>
              </div>
              <div v-if="selectedTooth.surfaces && selectedTooth.surfaces.length > 0">
                <span class="font-medium text-gray-700">Surfaces:</span>
                <div class="mt-2 space-y-2">
                  <div v-for="surface in selectedTooth.surfaces" :key="surface.surface_id"
                    class="flex items-start gap-2">
                    <div class="w-4 h-4 rounded border border-gray-300 flex-shrink-0 mt-0.5"
                      :style="{ backgroundColor: toothMarks[surface.surface_status]?.color || '#FFFFFF' }"></div>
                    <div class="flex-1">
                      <div class="text-gray-700 font-medium">{{ surface.surface_type }}</div>
                      <div class="text-gray-600 text-xs">
                        {{ surface.surface_status ? (toothMarks[surface.surface_status]?.name || 'Not Assessed') : 'Not Assessed' }}
                      </div>
                      <div class="text-gray-600 text-xs">Status: {{ formatStatusType(surface.status_type) }}</div>
                      <div class="text-gray-600 text-xs">Diagnosed By: {{ getUserName(surface.diagnosed_by) || 'Not Assigned' }}</div>
                      <div v-if="surface.surface_notes" class="text-gray-500 text-xs mt-1">{{ surface.surface_notes }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else>
                <span class="font-medium text-gray-700">Surfaces:</span>
                <p class="text-gray-500 mt-1 text-xs">No surfaces available</p>
              </div>
              <div v-if="selectedTooth.tooth_notes">
                <span class="font-medium text-gray-700">Notes:</span>
                <p class="text-gray-900 mt-1 text-sm">{{ selectedTooth.tooth_notes }}</p>
              </div>
            </div>
          </div>

          <!-- Treatment History -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Treatment History</h3>
            <div v-if="markedTeeth.length === 0" class="text-sm text-gray-500 text-center py-4">No treatments recorded
            </div>
            <div v-else class="space-y-3 max-h-96 overflow-y-auto">
              <div v-for="tooth in markedTeeth" :key="tooth.tooth_id" @click="selectTooth(tooth.tooth_number)"
                class="p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition">
                <div class="flex items-center justify-between mb-2">
                  <span class="font-semibold text-gray-900">Tooth #{{ tooth.tooth_number }}</span>
                  <span class="text-xs text-gray-500">{{ formatDate(tooth.last_updated) }}</span>
                </div>
                <div class="text-sm text-gray-700">{{ toothMarks[tooth.tooth_status]?.name || 'Not Assessed' }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ formatStatusType(tooth.status_type) }}</div>
                <div v-if="tooth.tooth_notes" class="text-xs text-gray-500 mt-1 line-clamp-2">{{ tooth.tooth_notes }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Edit Tooth Modal -->
      <div v-if="showEditModal && !isPatient"
        class="fixed inset-0 bg-black/20 backdrop-blur-sm bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
          <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between z-10">
            <h2 class="text-xl font-semibold text-gray-900">Edit Tooth #{{ selectedTooth?.tooth_number }}</h2>
            <button @click="closeEditModal" class="text-gray-400 hover:text-gray-600 transition">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="p-6 space-y-5">
            <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm">
              {{ error }}
            </div>

            <!-- Overall Tooth Status -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Overall Tooth Condition</label>
              <select v-model="editForm.tooth_status"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                <option :value="healthyMarkId">Healthy</option>
                <option v-for="(mark, id) in toothMarks" :key="id" :value="id">
                  {{ mark.name }}
                </option>
              </select>
            </div>

            <!-- Status Type (Tooth) -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Status Type <span class="text-red-500">*</span>
              </label>
              <select v-model="editForm.status_type"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                required>
                <option v-for="status in statusTypes" :key="status" :value="status">
                  {{ formatStatusType(status) }}
                </option>
              </select>
              <p class="text-xs text-gray-500 mt-1">Required field</p>
            </div>

            <!-- Diagnosed By (Tooth) -->
            <div v-if="editForm.status_type === 'treated_here' && !isPatient">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Diagnosed By
                <span v-if="isDentist" class="text-red-500">*</span>
              </label>
              <select v-model="editForm.diagnosed_by"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                :required="isDentist">
                <option :value="null">Select Dentist</option>
                <option v-for="dentist in filteredDentists" :key="dentist.user_id" :value="dentist.user_id">
                  {{ dentist.first_name }} {{ dentist.last_name }}
                </option>
              </select>
              <p v-if="isDentist" class="text-xs text-gray-500 mt-1">Required for treatments performed here</p>
            </div>

            <!-- General Notes -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">General Notes</label>
              <textarea v-model="editForm.tooth_notes" rows="2"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                placeholder="Add general notes about this tooth..."></textarea>
            </div>

            <!-- Surface Conditions -->
            <div>
              <h3 class="text-sm font-semibold text-gray-900 mb-3">Surface Conditions</h3>
              <div class="grid grid-cols-1 gap-3">
                <div v-for="(surface, index) in editForm.surfaces" :key="surface.surface_id"
                  class="border border-gray-200 rounded-lg p-3 space-y-3">
                  <div class="flex items-center gap-3">
                    <div class="w-4 h-4 rounded border border-gray-300 flex-shrink-0"
                      :style="{ backgroundColor: toothMarks[surface.surface_status]?.color || '#FFFFFF' }"></div>
                    <span class="font-medium text-gray-900 text-sm">{{ surface.surface_type }}</span>
                    <select v-model="surface.surface_status"
                      class="ml-auto text-sm border border-gray-300 rounded px-2 py-1 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                      <option :value="healthyMarkId">Healthy</option>
                      <option v-for="(mark, id) in toothMarks" :key="id" :value="id">
                        {{ mark.name }}
                      </option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                      Status Type <span class="text-red-500">*</span>
                    </label>
                    <select v-model="surface.status_type" @change="onSurfaceStatusTypeChange(index)"
                      class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                      :class="{ 'border-red-500': !statusTypes.includes(surface.status_type) }"
                      required>
                      <option v-for="status in statusTypes" :key="status" :value="status">
                        {{ formatStatusType(status) }}
                      </option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Required field</p>
                  </div>
                  <div v-if="surface.status_type === 'treated_here' && !isPatient">
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                      Diagnosed By
                      <span v-if="isDentist" class="text-red-500">*</span>
                    </label>
                    <select v-model="surface.diagnosed_by"
                      class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                      :required="isDentist">
                      <option :value="null">Select Dentist</option>
                      <option v-for="dentist in filteredDentists" :key="dentist.user_id" :value="dentist.user_id">
                        {{ dentist.first_name }} {{ dentist.last_name }}
                      </option>
                    </select>
                    <p v-if="isDentist" class="text-xs text-gray-500 mt-1">Required for treatments performed here</p>
                  </div>
                  <textarea v-model="surface.surface_notes" rows="1"
                    class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                    :placeholder="`Notes for ${surface.surface_type}...`"></textarea>
                </div>
              </div>
            </div>
          </div>

          <div
            class="sticky bottom-0 bg-gray-50 border-t border-gray-200 px-6 py-4 flex items-center justify-end gap-3">
            <button @click="closeEditModal" class="px-4 py-2 text-gray-700 hover:text-gray-900 transition">
              Cancel
            </button>
            <button @click="saveTooth" :disabled="isSaving || !canSave"
              class="px-4 py-2 bg-teal-700 text-white rounded-lg hover:bg-teal-800 transition disabled:opacity-50 disabled:cursor-not-allowed">
              {{ isSaving ? 'Saving...' : 'Save Changes' }}
            </button>
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
</style>