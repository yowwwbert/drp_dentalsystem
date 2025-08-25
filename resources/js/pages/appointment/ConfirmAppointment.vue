<script lang="ts" setup>
import { Label } from '@/components/ui/label';
import AppointmentLayout from '@/layouts/form/AppointmentLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { LoaderCircle } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { route } from 'ziggy-js';

interface AppointmentDetails {
  branch_id?: string;
  branch_name?: string;
  dentist_id?: string;
  dentist_name?: string;
  treatment_ids?: string[];
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

onMounted(() => {
  console.log('[ConfirmAppointment] Received props.appointment:', props.appointment);
  if (!props.appointment) {
    errorMessage.value = 'No appointment details provided. Please try again.';
  }
});

const form = useForm({
  branch_id: props.appointment?.branch_id ?? '',
  dentist_id: props.appointment?.dentist_id ?? '',
  treatment_ids: props.appointment?.treatment_ids ?? [],
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
  console.log('[ConfirmAppointment] Cancel clicked, posting back to appointment route');
  form.post(route('appointment'), {
    preserveState: true,
    preserveScroll: true,
    forceFormData: true,
  });
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
      <div v-if="errorMessage" class="text-red-500 text-sm p-2 bg-red-50 rounded-lg">
        {{ errorMessage }}
      </div>
      <div v-if="appointment">
        <div>
          <Label class="text-sm font-medium">Branch</Label>
          <p class="border rounded-lg p-2 w-full dark:bg-gray-800 dark:border-gray-600">
            {{ appointment.branch_name || 'Branch ID: ' + appointment.branch_id }}
          </p>
        </div>
        <div>
          <Label class="text-sm font-medium">Dentist</Label>
          <p class="border rounded-lg p-2 w-full dark:bg-gray-800 dark:border-gray-600">
            {{ appointment.dentist_name || 'Dentist ID: ' + appointment.dentist_id }}
          </p>
        </div>
        <div>
          <Label class="text-sm font-medium">Treatment(s)</Label>
          <p class="border rounded-lg p-2 w-full dark:bg-gray-800 dark:border-gray-600">
            {{ appointment.treatments?.map(t => t.treatment_name).join(', ') || appointment.treatment_ids?.join(', ') || 'No treatments selected' }}
          </p>
        </div>
        <div>
          <Label class="text-sm font-medium">Date</Label>
          <p class="border rounded-lg p-2 w-full dark:bg-gray-800 dark:border-gray-600">
            {{ appointment.schedule?.schedule_date || 'Not selected' }}
          </p>
        </div>
        <div>
          <Label class="text-sm font-medium">Time</Label>
          <p class="border rounded-lg p-2 w-full dark:bg-gray-800 dark:border-gray-600">
            {{ appointment.schedule?.start_time && appointment.schedule?.end_time ? `${appointment.schedule.start_time} - ${appointment.schedule.end_time}` : 'Not selected' }}
          </p>
        </div>
        <div class="flex justify-end gap-4 mt-4">
          <Button
            variant="outline"
            class="w-36"
            @click="cancel"
          >
            Cancel
          </Button>
          <Button
            type="submit"
            variant="secondary"
            class="w-36"
            :class="{ 'bg-[#3E7F7B] text-white hover:bg-[#3E7F7B]/50': !form.processing }"
            :disabled="form.processing || !appointment"
            @click="submitForm"
          >
            <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
            <span v-else>Confirm</span>
          </Button>
        </div>
      </div>
      <div v-else class="text-red-500 text-sm p-2 bg-red-50 rounded-lg">
        No appointment details available. Please try again.
      </div>
    </div>
  </AppointmentLayout>
</template>