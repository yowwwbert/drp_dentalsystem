<script lang="ts" setup>
import { Label } from '@/components/ui/label';
import AppointmentLayout from '@/layouts/form/AppointmentLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted, watch, computed } from 'vue';
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
    treatment_id?: string | number; // Make treatment_id optional in Schedule interface
}

const props = defineProps<{
    branch_id?: string | null;
    dentist_id?: string | null;
    treatment_id?: string | null;
}>();

const schedules = ref<Schedule[]>([]);
const errorMessage = ref<string>('');
const form = useForm({
    branch_id: props.branch_id || sessionStorage.getItem('selected_branch_id') || '',
    dentist_id: props.dentist_id || sessionStorage.getItem('selected_dentist_id') || '',
    schedule_id: '',
    selectedTime: '',
    treatment_id: props.treatment_id || sessionStorage.getItem('selected_treatment_id') || '', // Add fallback
});

// Compute available dates from schedules
const availableDates = computed(() => schedules.value.map(s => s.schedule_date));

const currentDate = new Date();
const currentMonth = ref(currentDate.getMonth());
const currentYear = ref(currentDate.getFullYear());
const daysInMonth = computed(() => new Date(currentYear.value, currentMonth.value + 1, 0).getDate());
const firstDayIndex = computed(() => new Date(currentYear.value, currentMonth.value, 1).getDay());

// Handle calendar date selection to set schedule_id
const selectDate = (dateStr: string) => {
    const schedule = schedules.value.find(s => s.schedule_date === dateStr);
    if (schedule) {
        form.schedule_id = schedule.schedule_id.toString();
    } else {
        form.schedule_id = '';
    }
};

onMounted(async () => {
    if (!props.branch_id || !props.dentist_id || !props.treatment_id) {
        errorMessage.value = 'Branch ID, Dentist ID, and Treatment ID are required.';
        return;
    }

    try {
        const response = await axios.get(route('appointment.dentist.schedule', {
            branch_id: props.branch_id,
            dentist_id: props.dentist_id,
        }), {
            params: { dentist_id: props.dentist_id }
        });
        schedules.value = response.data || [];
        if (!schedules.value.length) {
            errorMessage.value = 'No schedules available for this dentist at the selected branch.';
        } else if (form.schedule_id === '') {
            const todaySchedule = schedules.value.find(s => s.schedule_date === currentDate.toISOString().split('T')[0]);
            if (todaySchedule) {
                form.schedule_id = todaySchedule.schedule_id.toString();
            }
        }
    } catch (error) {
        errorMessage.value = 'Failed to load schedules. Please try again.';
    }
});

watch(() => form.schedule_id, (newScheduleId) => {
    if (newScheduleId) {
        const selectedSchedule = schedules.value.find(s => s.schedule_id === parseInt(newScheduleId) || s.schedule_id === newScheduleId);
        if (selectedSchedule) {
            form.schedule_id = selectedSchedule.schedule_id.toString();
        } else {
            form.schedule_id = '';
        }
    }
});

const submitForm = () => {
    if (!form.schedule_id || !form.treatment_id) {
        errorMessage.value = 'Please select a date, time, and ensure a treatment is selected.';
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

// Generate days for the calendar
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
                                'bg-[#3E7F7B]/25 dark:bg-green-900': day && day.isAvailable && day.dateStr !== new Date().toISOString().split('T')[0] && !schedules.some((s: Schedule) => s.schedule_id.toString() === form.schedule_id && s.schedule_date === day.dateStr),
                                'text-gray-400': !day,
                                'bg-[#1E4F4F] dark:bg-[#3E7F7B] text-white': day && schedules.some((s: Schedule) => s.schedule_id.toString() === form.schedule_id && s.schedule_date === day.dateStr),
                                'bg-[#3E7F7B] text-white dark:bg-yellow-800': day && day.dateStr === new Date().toISOString().split('T')[0] && !schedules.some((s: Schedule) => s.schedule_id.toString() === form.schedule_id && s.schedule_date === day.dateStr)
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
                    v-model="form.selectedTime"
                    class="border rounded-lg p-2 w-full dark:bg-gray-800 dark:border-gray-600"
                    :disabled="!form.schedule_id || !schedules.length"
                >
                    <option value="" disabled>Select a time</option>
                    <option
                        v-for="schedule in schedules.filter(s => s.schedule_id.toString() === form.schedule_id)"
                        :key="schedule.start_time"
                        :value="schedule.start_time"
                    >
                        {{ new Date(`${schedule.schedule_date} ${schedule.start_time}`).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }) }} - {{ new Date(`${schedule.schedule_date} ${schedule.end_time}`).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }) }}
                    </option>
                </select>
                <InputError v-if="form.errors.selectedTime" :message="form.errors.selectedTime" />
            </div>
            <div class="flex justify-end mt-4">
                <Button
                    type="submit"
                    :disabled="form.processing || !form.schedule_id || !form.selectedTime || !form.treatment_id"
                    variant="secondary"
                    class="w-36"
                    :class="{ 'bg-[#3E7F7B] text-white hover:bg-[#3E7F7B]/50': form.schedule_id && form.selectedTime && form.treatment_id }"
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