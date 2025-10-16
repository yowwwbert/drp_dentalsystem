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
  profile_picture?: string;
}

interface Treatment {
  treatment_id: string;
  treatment_name: string;
  treatment_duration: number;
}

const props = defineProps<{
  branch_id?: string;
  branch_name?: string;
}>();

const dentists = ref<Dentist[]>([]);
const treatments = ref<Treatment[]>([]);
const errorMessage = ref<string>('');
const selectedTreatments = ref<Treatment[]>([]);
const selectedDentistId = ref<string>('');

const form = useForm({
  branch_id: props.branch_id || sessionStorage.getItem('selected_branch_id') || '',
  branch_name: props.branch_name || sessionStorage.getItem('selected_branch_name') || '',
  dentist_id: '',
  dentist_name: '',
  treatment_ids: [] as string[],
  treatment_names: [] as string[],
});

// Computed properties to separate short and long treatments
const shortTreatments = computed(() => treatments.value.filter(t => t.treatment_duration <= 30));
const longTreatments = computed(() => treatments.value.filter(t => t.treatment_duration > 30));

onMounted(async () => {
  console.log('Initial props:', { branch_id: props.branch_id, branch_name: props.branch_name });
  console.log('Initial session storage:', {
    selected_branch_id: sessionStorage.getItem('selected_branch_id'),
    selected_branch_name: sessionStorage.getItem('selected_branch_name'),
  });
  console.log('Initial form:', form);

  if (!form.branch_name && sessionStorage.getItem('selected_branch_name')) {
    form.branch_name = sessionStorage.getItem('selected_branch_name') || '';
  }

  try {
    const treatmentResponse = await axios.get(route('appointment.treatments'));
    treatments.value = treatmentResponse.data || [];
    if (!treatments.value.length) {
      errorMessage.value = 'No treatments available. Please try again later.';
    }

    if (form.branch_id) {
  const dentistResponse = await axios.get(route('appointment.dentists', form.branch_id));
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

  console.log('Form after onMounted:', form);
});

const selectDentist = (dentist: Dentist) => {
  if (selectedDentistId.value === dentist.user_id) {
    selectedDentistId.value = '';
    form.dentist_id = '';
    form.dentist_name = '';
  } else {
    selectedDentistId.value = dentist.user_id;
    form.dentist_id = dentist.user_id;
    form.dentist_name = `${dentist.last_name}, ${dentist.first_name}`;
  }
};

const toggleTreatment = (t: Treatment) => {
  const exists = selectedTreatments.value.find(sel => sel.treatment_id === t.treatment_id);
  if (exists) {
    selectedTreatments.value = selectedTreatments.value.filter(sel => sel.treatment_id !== t.treatment_id);
  } else {
    if (isValidNextSelection(t)) {
      selectedTreatments.value.push(t);
    } else {
      errorMessage.value = "Invalid combination. You can pick 2 short (≤30 min), or 1 long (>30 min), or 1 long + 1 short.";
      setTimeout(() => (errorMessage.value = ""), 4000);
    }
  }
  form.treatment_ids = selectedTreatments.value.map(sel => sel.treatment_id);
  form.treatment_names = selectedTreatments.value.map(sel => sel.treatment_name);
};

const isValidNextSelection = (t: Treatment) => {
  const current = [...selectedTreatments.value, t];
  const longs = current.filter(c => {
    const isLong = c.treatment_duration > 30;
    console.log(`Treatment "${c.treatment_name}" duration: ${c.treatment_duration} => ${isLong ? 'long' : 'short'}`);
    return isLong;
  }).length;
  const shorts = current.filter(c => {
    const isShort = c.treatment_duration <= 30;
    console.log(`Treatment "${c.treatment_name}" duration: ${c.treatment_duration} => ${isShort ? 'short' : 'long'}`);
    return isShort;
  }).length;

  return (
    (longs === 0 && shorts <= 2) ||
    (longs === 1 && shorts === 0) ||
    (longs === 1 && shorts === 1)
  );
};

const submitForm = () => {
  if (!form.branch_name && sessionStorage.getItem('selected_branch_name')) {
    form.branch_name = sessionStorage.getItem('selected_branch_name') || '';
  }
  console.log('Submitting form with data:', form);
  if (form.branch_name) {
    sessionStorage.setItem('selected_branch_name', form.branch_name);
  }
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

      <!-- Dentist as Cards -->
      <div>
        <Label class="text-md font-medium mb-2 block">Dentist</Label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            v-for="dentist in dentists"
            :key="dentist.user_id"
            @click="selectDentist(dentist)"
            class="p-4 border rounded-xl cursor-pointer transition shadow-sm hover:shadow-md flex items-center gap-4"
            :class="selectedDentistId === dentist.user_id 
              ? 'bg-[#1E4F4F] dark:bg-[#3E7F7B] text-white hover:bg-[#3E7F7B]/50' 
              : 'border-gray-300 dark:border-gray-600'"
          >
            <img
              v-if="dentist.profile_picture"
              :src="dentist.profile_picture"
              alt="Profile picture"
              class="w-12 h-12 rounded-full object-cover"
            />
            <div v-else class="w-12 h-12 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
              <span class="text-gray-500 dark:text-gray-300">{{ dentist.first_name[0] }}{{ dentist.last_name[0] }}</span>
            </div>
            <div>Dr. {{ dentist.last_name }}, {{ dentist.first_name }}</div>
          </div>
        </div>
        <span v-if="form.errors.dentist_id" class="text-red-500 text-sm">
          {{ form.errors.dentist_id }}
        </span>
      </div>

      <!-- Treatments as Cards (Only show when dentist is selected) -->
      <div v-if="selectedDentistId">
        <Label class="text-md font-medium mb-2 block">Treatments</Label>
        
        <!-- Instructions -->
        <div class="mb-4 p-4 bg-[#1E4F4F]/10 dark:bg-[#1E4F4F] border-l-4 border-[#1E4F4F] dark:border-[#1E4F4F] rounded-md">
          <p class="text-sm font-medium text-[#1E4F4F] dark:text-white mb-2">
            Treatment choices are limited to the following options:
          </p>
          <ul class="list-disc list-inside text-sm text-[#1E4F4F] dark:text-white space-y-1">
            <li>2 short treatments (≤30 min each)</li>
            <li>1 long treatment (>30 min)</li>
            <li>1 long + 1 short</li>
          </ul>
        </div>

        <!-- Short Treatments Section -->
        <div v-if="shortTreatments.length > 0" class="mb-6">
          <Label class="text-sm font-medium mb-2 block text-gray-700 dark:text-gray-300">Short Treatments</Label>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
              v-for="t in shortTreatments"
              :key="t.treatment_id"
              @click="toggleTreatment(t)"
              class="p-4 border rounded-xl cursor-pointer transition shadow-sm hover:shadow-md font-medium"
              :class="selectedTreatments.some(sel => sel.treatment_id === t.treatment_id) 
                ? 'bg-[#1E4F4F] dark:bg-[#3E7F7B] text-white hover:bg-[#3E7F7B]/50' 
                : 'border-gray-300 dark:border-gray-600'"
            >
              <div>{{ t.treatment_name }}</div>
              <div
                class="text-xs"
                :class="selectedTreatments.some(sel => sel.treatment_id === t.treatment_id) 
                  ? 'text-gray-300' 
                  : 'text-gray-500 dark:text-gray-400'"
              >
                Expected time: {{ t.treatment_duration }} min
              </div>
            </div>
          </div>
        </div>

        <!-- Long Treatments Section -->
        <div v-if="longTreatments.length > 0">
          <Label class="text-sm font-medium mb-2 block text-gray-700 dark:text-gray-300">Long Treatments</Label>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
              v-for="t in longTreatments"
              :key="t.treatment_id"
              @click="toggleTreatment(t)"
              class="p-4 border rounded-xl cursor-pointer transition shadow-sm hover:shadow-md font-medium"
              :class="selectedTreatments.some(sel => sel.treatment_id === t.treatment_id) 
                ? 'bg-[#1E4F4F] dark:bg-[#3E7F7B] text-white hover:bg-[#3E7F7B]/50' 
                : 'border-gray-300 dark:border-gray-600'"
            >
              <div>{{ t.treatment_name }}</div>
              <div
                class="text-xs"
                :class="selectedTreatments.some(sel => sel.treatment_id === t.treatment_id) 
                  ? 'text-gray-300' 
                  : 'text-gray-500 dark:text-gray-400'"
              >
                Expected time: {{ t.treatment_duration }} min
              </div>
            </div>
          </div>
        </div>

        <span v-if="form.errors.treatment_ids" class="text-red-500 text-sm mt-2">
          {{ form.errors.treatment_ids }}
        </span>
      </div>

      <div class="flex justify-end mt-4">
        <Button
          type="submit"
          :disabled="form.processing || !form.dentist_id || !form.treatment_ids.length"
          variant="secondary"
          class="w-36"
          :class="{ 'bg-[#1E4F4F] text-white hover:bg-[#3E7F7B]/50': form.dentist_id && form.treatment_ids.length }"
        >
          <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
          <span v-else>Next</span>
        </Button>
      </div>
    </form>
  </AppointmentLayout>
</template>