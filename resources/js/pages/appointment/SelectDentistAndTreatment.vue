<script lang="ts" setup>
import { Label } from '@/components/ui/label';
import AppointmentLayout from '@/layouts/form/AppointmentLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { LoaderCircle } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { route } from 'ziggy-js';

interface Dentist {
  user_id: string;
  first_name: string;
  last_name: string;
}

interface Treatment {
  treatment_id: string;
  treatment_name: string;
  treatment_duration: number;
}

const props = defineProps<{
  branch_id?: string;
}>();

const dentists = ref<Dentist[]>([]);
const treatments = ref<Treatment[]>([]);
const errorMessage = ref<string>('');
const selectedTreatments = ref<Treatment[]>([]);

const form = useForm({
  branch_id: props.branch_id || sessionStorage.getItem('selected_branch_id') || '',
  dentist_id: '',
  treatment_ids: [] as string[],
});

// Fetch dentists + treatments
onMounted(async () => {
  try {
    const treatmentResponse = await axios.get(route('appointment.treatments'));
    treatments.value = treatmentResponse.data || [];
    if (!treatments.value.length) {
      errorMessage.value = 'No treatments available. Please try again later.';
    }

    if (form.branch_id) {
      const dentistResponse = await axios.get(route('appointment.dentists', {
        branch_id: form.branch_id
      }));
      dentists.value = dentistResponse.data || [];
      if (!dentists.value.length) {
        errorMessage.value = 'No dentists available for this branch.';
      }
    } else {
      errorMessage.value = 'No branch selected. Please go back and select a branch.';
    }
  } catch (error) {
    console.error('Error fetching data:', error);
    errorMessage.value = 'Failed to load data. Please try again.';
  }
});

// Toggle treatment selection
const toggleTreatment = (t: Treatment) => {
  const exists = selectedTreatments.value.find(sel => sel.treatment_id === t.treatment_id);
  if (exists) {
    selectedTreatments.value = selectedTreatments.value.filter(sel => sel.treatment_id !== t.treatment_id);
  } else {
    // Only allow valid combinations
    if (isValidNextSelection(t)) {
      selectedTreatments.value.push(t);
    } else {
      errorMessage.value = "Invalid combination. You can pick 2 short (≤30 min), or 1 long (>30 min), or 1 long + 1 short.";
      setTimeout(() => (errorMessage.value = ""), 4000);
    }
  }
  form.treatment_ids = selectedTreatments.value.map(sel => sel.treatment_id);
};

// Validation rules
const isValidNextSelection = (t: Treatment) => {
  const current = [...selectedTreatments.value, t];
  const longs = current.filter(c => {
    const isLong = c.treatment_duration > 30; // Changed from >= 30 to > 30
    console.log(`Treatment "${c.treatment_name}" duration: ${c.treatment_duration} => ${isLong ? 'long' : 'short'}`);
    return isLong;
  }).length;
  const shorts = current.filter(c => {
    const isShort = c.treatment_duration <= 30; // Changed from < 30 to <= 30
    console.log(`Treatment "${c.treatment_name}" duration: ${c.treatment_duration} => ${isShort ? 'short' : 'long'}`);
    return isShort;
  }).length;

  // valid cases
  return (
    (longs === 0 && shorts <= 2) || // max 2 short
    (longs === 1 && shorts === 0) || // exactly 1 long
    (longs === 1 && shorts === 1) // 1 long + 1 short
  );
};

const submitForm = () => {
  console.log('Submitting form with data:', form);
  form.post(route('dentist.store'), {
    preserveState: true,
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      console.log('Form submitted successfully');
    },
    onError: (errors) => {
      console.error('Form submission errors:', errors);
      errorMessage.value = 'Please correct the errors in the form: ' + Object.values(errors).join(', ');
    },
  });
};
</script>

<template>
  <AppointmentLayout
    title="Select Dentist & Treatment"
    description="Please select your preferred dentist and treatment for the appointment."
    :current-step="2"
  >
    <Head title="Book an Appointment" />
    <form @submit.prevent="submitForm" class="flex flex-col gap-6">
      <div v-if="errorMessage" class="text-red-500 text-sm p-2 bg-red-50 rounded-lg">
        {{ errorMessage }}
      </div>

      <!-- Dentist -->
      <div>
        <Label for="dentist" class="text-sm font-medium">Dentist</Label>
        <select
          id="dentist"
          v-model="form.dentist_id"
          class="border rounded-lg p-2 w-full dark:bg-gray-800 dark:border-gray-600"
          :disabled="!dentists.length"
        >
          <option value="" disabled>Select a dentist</option>
          <option v-for="dentist in dentists" :key="dentist.user_id" :value="dentist.user_id">
            Dr. {{ dentist.last_name }}, {{ dentist.first_name }}
          </option>
        </select>
        <span v-if="form.errors.dentist_id" class="text-red-500 text-sm">
          {{ form.errors.dentist_id }}
        </span>
      </div>

      <!-- Treatments as Cards -->
      <div>
        <Label class="text-sm font-medium mb-2 block">Treatments</Label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            v-for="t in treatments"
            :key="t.treatment_id"
            @click="toggleTreatment(t)"
            class="p-4 border rounded-xl cursor-pointer transition shadow-sm hover:shadow-md"
            :class="selectedTreatments.some(sel => sel.treatment_id === t.treatment_id) 
              ? 'bg-[#1E4F4F] dark:bg-[#3E7F7B] text-white hover:bg-[#3E7F7B]/50' 
              : 'border-gray-300 dark:border-gray-600'"
          >
            <div>{{ t.treatment_name }}</div>
          </div>
        </div>
        <span v-if="form.errors.treatment_ids" class="text-red-500 text-sm">
          {{ form.errors.treatment_ids }}
        </span>
      </div>

      <div class="flex justify-end mt-4">
        <Button
          type="submit"
          :disabled="form.processing || !form.dentist_id || !form.treatment_ids.length"
          variant="secondary"
          class="w-36"
          :class="{ 'bg-[#3E7F7B] text-white hover:bg-[#3E7F7B]/50': form.dentist_id && form.treatment_ids.length }"
        >
          <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
          <span v-else>Next</span>
        </Button>
      </div>
    </form>
  </AppointmentLayout>
</template>