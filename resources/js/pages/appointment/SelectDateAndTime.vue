<script lang="ts" setup>
import { Label } from '@/components/ui/label';
import AppointmentLayout from '@/layouts/form/AppointmentLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { LoaderCircle } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { route } from 'ziggy-js';
import InputError from '@/components/InputError.vue';

interface Schedule {
  dentist_id: string;
  branch_id: string;
  schedule_date: string;
  start_time: string;
  end_time: string;
  schedule_id: string | number;
  treatment_ids?: string[]; 
}

const props = defineProps<{
  branch_id?: string | null;
  dentist_id?: string | null;
  treatment_ids?: string[] | null;
}>();

const schedules = ref<Schedule[]>([]);
const errorMessage = ref<string>('');
const form = useForm({
  branch_id: props.branch_id || sessionStorage.getItem('selected_branch_id') || '',
  dentist_id: props.dentist_id || sessionStorage.getItem('selected_dentist_id') || '',
  schedule_id: '',
  treatment_ids: props.treatment_ids || JSON.parse(sessionStorage.getItem('selected_treatment_ids') || '[]'),
});

const selectedDate = ref<string>('');

const availableDates = computed(() => [...new Set(schedules.value.map(s => s.schedule_date))]);

const availableSchedules = computed(() => {
  if (!selectedDate.value) return [];
  return schedules.value.filter(s => s.schedule_date === selectedDate.value);
});

const currentDate = new Date();
const todayStr = currentDate.toISOString().split('T')[0];
const currentMonth = ref(currentDate.getMonth());
const currentYear = ref(currentDate.getFullYear());
const daysInMonth = computed(() => new Date(currentYear.value, currentMonth.value + 1, 0).getDate());
const firstDayIndex = computed(() => new Date(currentYear.value, currentMonth.value, 1).getDay());

const selectDate = (dateStr: string) => {
  if (selectedDate.value === dateStr) {
    selectedDate.value = '';
    form.schedule_id = '';
  } else {
    selectedDate.value = dateStr;
    form.schedule_id = '';
  }
};

onMounted(async () => {
  if (!form.branch_id || !form.dentist_id || !form.treatment_ids.length) {
    errorMessage.value = 'Branch ID, Dentist ID, and at least one Treatment ID are required.';
    return;
  }

  try {
    const response = await axios.get(route('appointment.dentist.schedule', {
      branch_id: form.branch_id,
      dentist_id: form.dentist_id,
    }), {
      params: { dentist_id: form.dentist_id }
    });
    schedules.value = response.data || [];
    if (!schedules.value.length) {
      errorMessage.value = 'No schedules available for this dentist at the selected branch.';
    } else {
      if (availableDates.value.includes(todayStr)) {
        selectedDate.value = todayStr;
      }
    }
  } catch (error) {
    errorMessage.value = 'Failed to load schedules. Please try again.';
  }
});

const submitForm = () => {
  if (!form.schedule_id || !form.treatment_ids.length) {
    errorMessage.value = 'Please select a date, time, and ensure at least one treatment is selected.';
    return;
  }
  console.log('Submitting form with data:', form);
  form.post(route('appointment.store'), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => console.log('Form submitted successfully'),
    onError: (errors) => {
      console.error('Form submission errors:', errors);
      errorMessage.value = 'Please correct the errors: ' + Object.values(errors).join(', ');
    },
  });
};

const days = computed(() => {
  const daysArray = [];
  for (let i = 0; i < firstDayIndex.value; i++) {
    daysArray.push(null);
  }
  for (let day = 1; day <= daysInMonth.value; day++) {
    const dateStr = `${currentYear.value}-${String(currentMonth.value + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    daysArray.push({
      day,
      isAvailable: availableDates.value.includes(dateStr),
      dateStr,
    });
  }
  return daysArray;
});
</script>

<template>
  <AppointmentLayout
    title="Select Date & Time"
    description="Please select your preferred date and time for the appointment."
    :current-step="3"
  >
    <Head title="Book an Appointment" />
    <form @submit.prevent="submitForm" class="flex flex-col gap-6">
      <div v-if="errorMessage" class="text-red-500 text-sm p-2 bg-red-50 rounded-lg">
        {{ errorMessage }}
      </div>
      <div>
        <Label class="text-sm font-medium">Calendar</Label>
        <div class="mt-2">
          <div class="flex justify-between items-center mb-2">
            <span class="text-lg font-semibold">{{ new Date(currentYear, currentMonth).toLocaleString('default', { month: 'long', year: 'numeric' }) }}</span>
          </div>
          <div class="grid grid-cols-7 gap-2 text-center">
            <div class="font-medium" v-for="dayName in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" :key="dayName">{{ dayName }}</div>
            <div
              v-for="(day, index) in days"
              :key="index"
              class="p-2 border rounded-lg cursor-pointer hover:bg-green-100 dark:hover:bg-gray-700"
              :class="{
                'bg-[#3E7F7B]/25 dark:bg-green-900': day && day.isAvailable && day.dateStr !== selectedDate && day.dateStr !== todayStr,
                'text-gray-400': !day,
                'bg-[#1E4F4F] dark:bg-[#3E7F7B] text-white': day && day.dateStr === selectedDate,
                'bg-[#3E7F7B] text-white dark:bg-yellow-800': day && day.dateStr === todayStr && day.dateStr !== selectedDate && day.isAvailable
              }"
              @click="day ? selectDate(day.dateStr) : null"
            >
              {{ day ? day.day : '' }}
            </div>
            <div class="col-span-7 text-sm gap-2 text-gray-500 mt-2 flex items-center space-x-2">
              <span class="bg-[#3E7F7B] text-white dark:bg-yellow-800 px-2 py-1 rounded">Today</span>
              <span class="bg-[#3E7F7B]/25 dark:bg-green-900 px-2 py-1 rounded">Available</span>
              <span class="bg-[#1E4F4F] dark:bg-[#3E7F7B] text-white px-2 py-1 rounded">Selected</span>
            </div>  
          </div>
        </div>
      </div>
      <div>
        <Label for="time" class="text-sm font-medium">Time</Label>
        <select
          id="time"
          v-model="form.schedule_id"
          class="border rounded-lg p-2 w-full dark:bg-gray-800 dark:border-gray-600"
          :disabled="!selectedDate || !availableSchedules.length"
        >
          <option value="" disabled>Select a time</option>
          <option
            v-for="schedule in availableSchedules"
            :key="schedule.schedule_id"
            :value="schedule.schedule_id.toString()"
          >
            {{ new Date(`${schedule.schedule_date} ${schedule.start_time}`).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }) }} - {{ new Date(`${schedule.schedule_date} ${schedule.end_time}`).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }) }}
          </option>
        </select>
        <InputError v-if="form.errors.schedule_id" :message="form.errors.schedule_id" />
      </div>
      <div class="flex justify-end mt-4">
        <Button
          type="submit"
          :disabled="form.processing || !form.schedule_id || !form.treatment_ids.length"
          variant="secondary"
          class="w-36"
          :class="{ 'bg-[#3E7F7B] text-white hover:bg-[#3E7F7B]/50': form.schedule_id && form.treatment_ids.length }"
        >
          <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
          <span v-else>Next</span>
        </Button>
      </div>
    </form>
  </AppointmentLayout>
</template>

<style scoped>
.grid-cols-7 {
  grid-template-columns: repeat(7, minmax(0, 1fr));
}
</style>
