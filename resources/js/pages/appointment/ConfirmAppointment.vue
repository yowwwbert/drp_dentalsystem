<script lang="ts" setup>
import { Label } from '@/components/ui/label';
import AppointmentLayout from '@/layouts/form/AppointmentLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import { LoaderCircle, MapPin, UserRound, Stethoscope, Calendar, Clock, X, Check } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { route } from 'ziggy-js';

interface AppointmentDetails {
  branch_id?: string;
  branch_name?: string;
  dentist_id?: string;
  dentist_name?: string;
  treatment_ids?: string[];
  treatment_names?: string[];
  treatments?: Array<{ treatment_id: string; treatment_name: string }>;
  schedule?: {
    schedule_id: string;
    schedule_date: string;
    start_time: string;
    end_time: string;
  };
}

const props = defineProps<{
  appointment?: AppointmentDetails;
  errors?: Record<string, string>;
}>();

const errorMessage = ref<string>('');

// Fallback to sessionStorage for treatment_names if not in props
const treatmentNames = ref<string[]>(
  props.appointment?.treatment_names?.length
    ? props.appointment.treatment_names
    : JSON.parse(sessionStorage.getItem('selected_treatment_names') || '[]')
);

// Computed property for day of the week
const dayOfWeek = computed(() => {
  if (!props.appointment?.schedule?.schedule_date) return '';
  try {
    const date = new Date(props.appointment.schedule.schedule_date);
    if (isNaN(date.getTime())) return ''; // Handle invalid date
    return date.toLocaleDateString('en-PH', { weekday: 'long' });
  } catch {
    return ''; // Return empty string if date parsing fails
  }
});

// Computed property for formatted time
const formattedTime = computed(() => {
  if (!props.appointment?.schedule?.start_time || !props.appointment?.schedule?.end_time) {
    return 'Not selected';
  }
  const startTime = new Date(`${props.appointment.schedule.schedule_date} ${props.appointment.schedule.start_time}`);
  const endTime = new Date(`${props.appointment.schedule.schedule_date} ${props.appointment.schedule.end_time}`);
  return `${startTime.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', hour12: true })} - ${endTime.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', hour12: true })}`;
});

// Computed property for treatment display
const displayTreatments = computed(() => {
  if (treatmentNames.value.length) return treatmentNames.value.join(', ');
  if (props.appointment?.treatments?.length) {
    return props.appointment.treatments.map(t => t.treatment_name).join(', ');
  }
  if (props.appointment?.treatment_ids?.length) {
    return props.appointment.treatment_ids.join(', ');
  }
  return 'No treatments selected';
});

onMounted(() => {
  console.log('[ConfirmAppointment] Received props.appointment:', props.appointment);
  console.log('[ConfirmAppointment] Session storage:', {
    selected_branch_name: sessionStorage.getItem('selected_branch_name'),
    selected_dentist_name: sessionStorage.getItem('selected_dentist_name'),
    selected_treatment_names: sessionStorage.getItem('selected_treatment_names'),
  });
  console.log('[ConfirmAppointment] Using treatment_names:', treatmentNames.value);
  if (!props.appointment) {
    errorMessage.value = 'No appointment details provided. Please try again.';
  } else if (!treatmentNames.value.length && !props.appointment.treatments?.length) {
    console.warn('[ConfirmAppointment] No treatment names or treatments provided, falling back to IDs');
  }
});

const form = useForm({
  branch_id: props.appointment?.branch_id ?? '',
  dentist_id: props.appointment?.dentist_id ?? '',
  treatment_ids: props.appointment?.treatment_ids ?? [],
  treatment_names: treatmentNames.value,
  schedule_id: props.appointment?.schedule?.schedule_id ?? '',
});

const submitForm = () => {
  if (!props.appointment) {
    errorMessage.value = 'Cannot confirm appointment: No details available.';
    return;
  }
  console.log('[ConfirmAppointment] Sending form data to appointment.confirm:', form.data());
  form.post(route('appointment.confirm'), {
    preserveState: true,
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      console.log('[ConfirmAppointment] Appointment confirmed successfully');
    },
    onError: (errors) => {
      console.error('[ConfirmAppointment] Confirmation errors:', errors);
      errorMessage.value = 'Failed to confirm appointment: ' + Object.values(errors).join(', ');
    },
  });
};

const cancel = () => {
  console.log('[ConfirmAppointment] Cancel clicked, redirecting to home route');
  router.get(route('home'));
};
</script>

<template>
  <AppointmentLayout
    title="Confirm Appointment"
    description="Please review your appointment details and confirm or cancel."
    :current-step="5"
  >
    <Head title="Confirm Appointment" />
    <div class="flex flex-col gap-6">
      <div v-if="errorMessage" class="text-red-500 text-md p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
        {{ errorMessage }}
      </div>
      
      <div v-if="appointment" class="space-y-4">
        <!-- Summary Card -->
        <div class="bg-[#3E7F7B]/10 dark:bg-[#3E7F7B]/20 border border-[#3E7F7B]/30 rounded-xl p-6">
          <h3 class="text-lg font-semibold text-[#3E7F7B] dark:text-[#5FA9A5] mb-4">Appointment Summary</h3>
          
          <!-- Branch -->
          <div class="flex items-start gap-3 pb-4">
            <div class="p-2 bg-[#3E7F7B]/20 rounded-lg mt-1">
              <MapPin class="h-5 w-5 text-[#3E7F7B] dark:text-[#5FA9A5]" />
            </div>
            <div class="flex-1">
              <Label class="text-sm font-medium text-gray-600 dark:text-gray-400">Branch Location</Label>
              <p class="text-md font-medium">
                {{ appointment.branch_name || 'Branch ID: ' + appointment.branch_id }}
              </p>
            </div>
          </div>

          <!-- Dentist -->
          <div class="flex items-start gap-3 pb-4">
            <div class="p-2 bg-[#3E7F7B]/20 rounded-lg mt-1">
              <UserRound class="h-5 w-5 text-[#3E7F7B] dark:text-[#5FA9A5]" />
            </div>
            <div class="flex-1">
              <Label class="text-sm font-medium text-gray-600 dark:text-gray-400">Dentist</Label>
              <p class="text-md font-medium">
                Dr. {{ appointment.dentist_name || 'Dentist ID: ' + appointment.dentist_id }}
              </p>
            </div>
          </div>

          <!-- Treatment(s) -->
          <div class="flex items-start gap-3 pb-4">
            <div class="p-2 bg-[#3E7F7B]/20 rounded-lg mt-1">
              <Stethoscope class="h-5 w-5 text-[#3E7F7B] dark:text-[#5FA9A5]" />
            </div>
            <div class="flex-1">
              <Label class="text-sm font-medium text-gray-600 dark:text-gray-400">Treatment(s)</Label>
              <p class="text-md font-medium">
                {{ displayTreatments }}
              </p>
            </div>
          </div>

          <!-- Date -->
          <div class="flex items-start gap-3 pb-4">
            <div class="p-2 bg-[#3E7F7B]/20 rounded-lg mt-1">
              <Calendar class="h-5 w-5 text-[#3E7F7B] dark:text-[#5FA9A5]" />
            </div>
            <div class="flex-1">
              <Label class="text-sm font-medium text-gray-600 dark:text-gray-400">Date</Label>
              <p class="text-md font-medium">
                {{ 
                  appointment.schedule?.schedule_date 
                    ? `${dayOfWeek ? dayOfWeek + ', ' : ''}${new Date(appointment.schedule.schedule_date).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' })}`
                    : 'Not selected' 
                }}
              </p>
            </div>
          </div>

          <!-- Time -->
          <div class="flex items-start gap-3">
            <div class="p-2 bg-[#3E7F7B]/20 rounded-lg mt-1">
              <Clock class="h-5 w-5 text-[#3E7F7B] dark:text-[#5FA9A5]" />
            </div>
            <div class="flex-1">
              <Label class="text-sm font-medium text-gray-600 dark:text-gray-400">Time</Label>
              <p class="text-md font-medium">
                {{ 
                  appointment.schedule?.start_time && appointment.schedule?.end_time 
                    ? `${new Date(appointment.schedule.start_time).toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', hour12: true })} - 
                       ${new Date(appointment.schedule.end_time).toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', hour12: true })}` 
                    : 'Not selected' 
                }}
              </p>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end gap-3 mt-6">
          <Button
            variant="outline"
            class="w-36 border-gray-300 hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-800"
            @click="cancel"
            :disabled="form.processing"
          >
            <X class="h-4 w-4 mr-2" />
            Cancel
          </Button>
          <Button
            type="submit"
            class="w-36 bg-[#3E7F7B] text-white hover:bg-[#2E6663]"
            :disabled="form.processing || !appointment"
            @click="submitForm"
          >
            <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
            <Check v-else class="h-4 w-4 mr-2" />
            <span>{{ form.processing ? 'Processing...' : 'Confirm' }}</span>
          </Button>
        </div>
      </div>

      <div v-else class="text-red-500 text-md p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
        No appointment details available. Please try again.
      </div>
    </div>
  </AppointmentLayout>
</template>