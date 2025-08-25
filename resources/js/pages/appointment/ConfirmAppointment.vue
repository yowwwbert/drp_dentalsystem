<script lang="ts" setup>
import { Label } from '@/components/ui/label';
import AppointmentLayout from '@/layouts/form/AppointmentLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { LoaderCircle } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { route } from 'ziggy-js';

interface AppointmentDetails {
  branch_id: string;
  dentist_id: string;
  treatment_id: string;
  schedule: {
    schedule_id: string;
    schedule_date: string;
    start_time: string;
    end_time: string;
  };
}

const props = defineProps<{
  appointment: AppointmentDetails;
  errors?: Record<string, string>;
}>();

const errorMessage = ref<string>('');

// ✅ Log what is received from backend
onMounted(() => {
  console.log('[ConfirmAppointment] Received props.appointment:', props.appointment);
});

const form = useForm({
  branch_id: props.appointment.branch_id,
  dentist_id: props.appointment.dentist_id,
  treatment_id: props.appointment.treatment_id,
  schedule_id: props.appointment.schedule.schedule_id,
});

// ✅ Log what will be sent before submitting
const submitForm = () => {
  console.log('[ConfirmAppointment] Sending form data to appointment.store:', form.data());

  form.post(route('appointment.confirm'), {
    preserveState: true,
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      console.log('[ConfirmAppointment] Appointment confirmed successfully');
    },
    onError: (errors) => {
      console.error('[ConfirmAppointment] Confirmation errors:', errors);
      errorMessage.value =
        'Failed to confirm appointment: ' + Object.values(errors).join(', ');
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
      <div>
        <Label class="text-sm font-medium">Branch</Label>
        <p class="border rounded-lg p-2 w-full dark:bg-gray-800 dark:border-gray-600">
          {{ 'Branch ID: ' + props.appointment.branch_id }}
        </p>
      </div>
      <div>
        <Label class="text-sm font-medium">Dentist</Label>
        <p class="border rounded-lg p-2 w-full dark:bg-gray-800 dark:border-gray-600">
          {{ 'Dentist ID: ' + props.appointment.dentist_id }}
        </p>
      </div>
      <div>
        <Label class="text-sm font-medium">Treatment</Label>
        <p class="border rounded-lg p-2 w-full dark:bg-gray-800 dark:border-gray-600">
          {{ 'Treatment ID: ' + props.appointment.treatment_id }}
        </p>
      </div>
      <div>
        <Label class="text-sm font-medium">Date</Label>
        <p class="border rounded-lg p-2 w-full dark:bg-gray-800 dark:border-gray-600">
          {{ props.appointment.schedule.schedule_date }}
        </p>
      </div>
      <div>
        <Label class="text-sm font-medium">Time</Label>
        <p class="border rounded-lg p-2 w-full dark:bg-gray-800 dark:border-gray-600">
          {{ props.appointment.schedule.start_time }} - {{ props.appointment.schedule.end_time }}
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
          :disabled="form.processing"
          @click="submitForm"
        >
          <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
          <span v-else>Confirm</span>
        </Button>
      </div>
    </div>
  </AppointmentLayout>
</template>