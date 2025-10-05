<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppointmentLayout from '@/layouts/form/AppointmentLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { Calendar, CircleChevronLeft, CircleChevronRight, Clock, LoaderCircle } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { route } from 'ziggy-js';

interface Schedule {
    dentist_id: string;
    branch_id: string;
    schedule_date: string;
    start_time: string;
    end_time: string;
    schedule_id: string | number;
    treatment_ids?: string[];
    branch_name?: string;
    dentist_name?: string;
}

const props = defineProps<{
    branch_id?: string | null;
    dentist_id?: string | null;
    treatment_ids?: string[] | null;
    treatment_names?: string[] | null;
    branch_name?: string | null;
    dentist_name?: string | null;
}>();

const schedules = ref<Schedule[]>([]);
const errorMessage = ref<string>('');
const form = useForm({
    branch_id: props.branch_id || sessionStorage.getItem('selected_branch_id') || '',
    dentist_id: props.dentist_id || sessionStorage.getItem('selected_dentist_id') || '',
    branch_name: props.branch_name || sessionStorage.getItem('selected_branch_name') || '',
    dentist_name: props.dentist_name || sessionStorage.getItem('selected_dentist_name') || '',
    schedule_id: '',
    schedule_date: '', // Added schedule_date
    treatment_ids: props.treatment_ids || JSON.parse(sessionStorage.getItem('selected_treatment_ids') || '[]'),
    treatment_names: props.treatment_names || JSON.parse(sessionStorage.getItem('selected_treatment_names') || '[]'),
});

console.log('Initial props:', props);
console.log('Initial session storage:', {
    selected_branch_id: sessionStorage.getItem('selected_branch_id'),
    selected_branch_name: sessionStorage.getItem('selected_branch_name'),
    selected_dentist_id: sessionStorage.getItem('selected_dentist_id'),
    selected_dentist_name: sessionStorage.getItem('selected_dentist_name'),
    selected_treatment_ids: sessionStorage.getItem('selected_treatment_ids'),
    selected_treatment_names: sessionStorage.getItem('selected_treatment_names'),
});
console.log('Initial form:', form);

const selectedDate = ref<string>('');

const availableDates = computed(() => [...new Set(schedules.value.map((s) => s.schedule_date))]);

const availableSchedules = computed(() => {
    if (!selectedDate.value) return [];
    return schedules.value
        .filter((s) => s.schedule_date === selectedDate.value)
        .sort((a, b) => {
            const timeA = new Date(`${a.schedule_date} ${a.start_time}`).getTime();
            const timeB = new Date(`${b.schedule_date} ${b.start_time}`).getTime();
            return timeA - timeB;
        });
});

const selectedSchedule = computed(() => {
    if (!form.schedule_id) return null;
    return schedules.value.find((s) => s.schedule_id.toString() === form.schedule_id);
});

const formattedSelectedDate = computed(() => {
    if (!selectedDate.value) return '';
    const date = new Date(selectedDate.value + 'T00:00:00');
    return date.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
});

const formattedSelectedTime = computed(() => {
    if (!selectedSchedule.value) return '';
    const startTime = new Date(`${selectedSchedule.value.schedule_date} ${selectedSchedule.value.start_time}`);
    const endTime = new Date(`${selectedSchedule.value.schedule_date} ${selectedSchedule.value.end_time}`);
    return `${startTime.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true })} - ${endTime.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true })}`;
});

// Get today's date in local timezone
const today = new Date();
today.setHours(0, 0, 0, 0);
const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

const currentMonth = ref(today.getMonth());
const currentYear = ref(today.getFullYear());
const daysInMonth = computed(() => new Date(currentYear.value, currentMonth.value + 1, 0).getDate());
const firstDayIndex = computed(() => new Date(currentYear.value, currentMonth.value, 1).getDay());

// Month navigation functions - don't reset anything
const prevMonth = () => {
    currentMonth.value--;
    if (currentMonth.value < 0) {
        currentMonth.value = 11;
        currentYear.value--;
    }
};

const nextMonth = () => {
    currentMonth.value++;
    if (currentMonth.value > 11) {
        currentMonth.value = 0;
        currentYear.value++;
    }
};

const selectDate = (dateStr: string) => {
    if (!availableDates.value.includes(dateStr)) return;
    if (selectedDate.value === dateStr) {
        // Deselect date
        selectedDate.value = '';
        form.schedule_id = '';
        form.schedule_date = ''; // Reset schedule_date
    } else {
        // Select new date - reset schedule_id and schedule_date
        selectedDate.value = dateStr;
        form.schedule_id = '';
        form.schedule_date = dateStr; // Set schedule_date
    }
};

const selectSchedule = (scheduleId: string | number) => {
    form.schedule_id = scheduleId.toString();
    const selected = schedules.value.find((s) => s.schedule_id.toString() === scheduleId.toString());
    if (selected) {
        form.schedule_date = selected.schedule_date; // Update schedule_date
    }
};

onMounted(async () => {
    if (!form.branch_id || !form.dentist_id || !form.treatment_ids.length) {
        errorMessage.value = 'Branch ID, Dentist ID, and at least one Treatment ID are required.';
        return;
    }

    if (!form.branch_name && sessionStorage.getItem('selected_branch_name')) {
        form.branch_name = sessionStorage.getItem('selected_branch_name') || '';
    }
    if (!form.dentist_name && sessionStorage.getItem('selected_dentist_name')) {
        form.dentist_name = sessionStorage.getItem('selected_dentist_name') || '';
    }
    if (!form.treatment_names.length && sessionStorage.getItem('selected_treatment_names')) {
        form.treatment_names = JSON.parse(sessionStorage.getItem('selected_treatment_names') || '[]');
    }

    console.log('Form after session storage check:', form);

    try {
        const response = await axios.get(
            route('appointment.dentist.schedule', {
                branch_id: form.branch_id,
                dentist_id: form.dentist_id,
            }),
        );
        schedules.value = response.data || [];
        console.log('Fetched schedules:', schedules.value);
        if (!schedules.value.length) {
            errorMessage.value = 'No schedules available for this dentist at the selected branch.';
        } else {
            if (availableDates.value.includes(todayStr)) {
                selectedDate.value = todayStr;
                form.schedule_date = todayStr; // Set initial schedule_date
            }
        }
    } catch (error) {
        console.error('Error fetching schedules:', error);
        errorMessage.value = 'Failed to load schedules. Please try again.';
    }

    console.log('Form after fetching schedules:', form);
});

const submitForm = () => {
    if (!form.schedule_id || !form.schedule_date || !form.treatment_ids.length) {
        errorMessage.value = 'Please select a date, time, and ensure at least one treatment is selected.';
        return;
    }
    console.log('Submitting form with data:', form);
    if (form.branch_name) {
        sessionStorage.setItem('selected_branch_name', form.branch_name);
    }
    if (form.dentist_name) {
        sessionStorage.setItem('selected_dentist_name', form.dentist_name);
    }
    if (form.treatment_names.length) {
        sessionStorage.setItem('selected_treatment_names', JSON.stringify(form.treatment_names));
    }
    console.log('Session storage after update:', {
        selected_branch_name: sessionStorage.getItem('selected_branch_name'),
        selected_dentist_name: sessionStorage.getItem('selected_dentist_name'),
        selected_treatment_names: sessionStorage.getItem('selected_treatment_names'),
    });

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
            isToday: dateStr === todayStr,
        });
    }
    return daysArray;
});
</script>

<template>
    <AppointmentLayout title="Select Date & Time" description="Please select your preferred date and time for the appointment." :current-step="3">
        <Head title="Book an Appointment" />
        <form @submit.prevent="submitForm" class="flex flex-col gap-4">
            <div v-if="errorMessage" class="rounded-lg bg-red-50 p-2 text-sm text-red-500">
                {{ errorMessage }}
            </div>

            <!-- Selected Date & Time Display -->
            <div v-if="selectedDate || form.schedule_id" class="rounded-lg border border-[#3E7F7B]/30 bg-[#3E7F7B]/10 p-4 dark:bg-[#3E7F7B]/20">
                <h3 class="mb-2 text-sm font-semibold text-[#3E7F7B] dark:text-[#5FA9A5]">Your Selection</h3>
                <div class="space-y-2">
                    <div v-if="selectedDate" class="flex items-center gap-2 text-sm">
                        <Calendar class="h-4 w-4 text-[#3E7F7B] dark:text-[#5FA9A5]" />
                        <span class="font-medium">Date:</span>
                        <span>{{ formattedSelectedDate }}</span>
                    </div>
                    <div v-if="form.schedule_id" class="flex items-center gap-2 text-sm">
                        <Clock class="h-4 w-4 text-[#3E7F7B] dark:text-[#5FA9A5]" />
                        <span class="font-medium">Time:</span>
                        <span>{{ formattedSelectedTime }}</span>
                    </div>
                </div>
            </div>
            <div>
                <Label class="text-md mb-2 font-medium">Select Date</Label>

                <div>
                    <div
                        class="mb-2 flex items-center justify-between rounded-xl rounded-br-none rounded-bl-none bg-[#1E4F4F] p-3 px-6 text-white dark:bg-gray-800"
                    >
                        <!-- Prev Month -->
                        <button type="button" @click="prevMonth" class="flex items-center justify-center">
                            <CircleChevronLeft class="h-6 w-6 text-white hover:text-[#2E6663]" />
                        </button>

                        <!-- Month Label -->
                        <span class="text-lg font-semibold">
                            {{ new Date(currentYear, currentMonth, 1).toLocaleString('default', { month: 'long', year: 'numeric' }) }}
                        </span>

                        <!-- Next Month -->
                        <button type="button" @click="nextMonth" class="flex items-center justify-center">
                            <CircleChevronRight class="h-6 w-6 text-white hover:text-[#2E6663]" />
                        </button>
                    </div>

                    <div class="grid grid-cols-7 gap-3 rounded-xl rounded-tl-none rounded-tr-none bg-gray-100 p-4 pt-0 text-center dark:bg-gray-800">
                        <div class="font-medium" v-for="dayName in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" :key="dayName">
                            {{ dayName }}
                        </div>
                        <div
                            v-for="(day, index) in days"
                            :key="index"
                            class="rounded-lg p-3 shadow-md"
                            :class="{
                                'text-gray-400': !day,
                                'cursor-pointer bg-[#3E7F7B]/25 hover:bg-[#3E7F7B]/50 dark:bg-green-900':
                                    day && day.isAvailable && day.dateStr !== selectedDate && !day.isToday,
                                'bg-[#3E7F7B] text-white dark:bg-yellow-800': day && day.isToday && day.dateStr !== selectedDate,
                                'cursor-pointer': day && day.isToday && day.isAvailable && day.dateStr !== selectedDate,
                                'cursor-pointer bg-[#1E4F4F] text-white dark:bg-[#3E7F7B]': day && day.dateStr === selectedDate,
                                'cursor-not-allowed opacity-50': day && !day.isAvailable && !day.isToday,
                            }"
                            role="button"
                            :tabindex="day && day.isAvailable ? 0 : -1"
                            :aria-selected="day && day.dateStr === selectedDate"
                            @click="day && day.isAvailable ? selectDate(day.dateStr) : null"
                            @keydown.enter="day && day.isAvailable ? selectDate(day.dateStr) : null"
                            @keydown.space.prevent="day && day.isAvailable ? selectDate(day.dateStr) : null"
                        >
                            {{ day ? day.day : '' }}
                        </div>
                    </div>
                    <div class="col-span-7 flex items-center justify-end gap-2 space-x-2 text-sm text-gray-500">
                        <span class="font-medium">Legend:</span>
                        <span class="rounded bg-[#3E7F7B] px-2 py-1 text-white dark:bg-yellow-800">Today</span>
                        <span class="rounded bg-[#3E7F7B]/25 px-2 py-1 dark:bg-green-900">Available</span>
                        <span class="rounded bg-[#1E4F4F] px-2 py-1 text-white dark:bg-[#3E7F7B]">Selected</span>
                    </div>
                </div>
            </div>

            <div>
                <Label class="text-md font-medium">Select Time</Label>
                <div v-if="!selectedDate" class="mt-2 text-sm text-gray-500">Please select a date first</div>
                <div v-else-if="!availableSchedules.length" class="mt-2 text-sm text-gray-500">No time slots available for this date</div>
                <div v-else class="mt-2 grid grid-cols-2 gap-3">
                    <button
                        type="button"
                        v-for="schedule in availableSchedules"
                        :key="schedule.schedule_id"
                        @click="selectSchedule(schedule.schedule_id)"
                        class="rounded-lg shadow-md p-3 text-left transition-all hover:shadow-md hover:bg-[#3E7F7B]/25"
                        :class="{
                            'bg-[#1E4F4F] text-white': form.schedule_id === schedule.schedule_id.toString(),
                            'border-gray-300 hover:border-[#3E7F7B] dark:border-gray-600 dark:hover:border-[#3E7F7B]':
                                form.schedule_id !== schedule.schedule_id.toString(),
                        }"
                    >
                        <div class="flex items-center gap-2">
                            <Clock class="h-6 w-6" :class="form.schedule_id === schedule.schedule_id.toString() ? 'text-white' : 'text-[#3E7F7B]'" />
                            <div class="text-sm font-medium">
                                {{
                                    new Date(`${schedule.schedule_date} ${schedule.start_time}`).toLocaleTimeString('en-US', {
                                        hour: '2-digit',
                                        minute: '2-digit',
                                        hour12: true,
                                    })
                                }}
                                <br>
                                <span
                                    class="mt-1 text-xs font-normal"
                                    :class="form.schedule_id === schedule.schedule_id.toString() ? 'text-white/80' : 'text-gray-500'"
                                >
                                    to
                                    {{
                                        new Date(`${schedule.schedule_date} ${schedule.end_time}`).toLocaleTimeString('en-US', {
                                            hour: '2-digit',
                                            minute: '2-digit',
                                            hour12: true,
                                        })
                                    }}
                                </span>
                            </div>
                        </div>
                    </button>
                </div>
                <InputError v-if="form.errors.schedule_id" :message="form.errors.schedule_id" class="mt-2" />
            </div>

            <div class="mt-4 flex justify-end">
                <Button
                    type="submit"
                    :disabled="form.processing || !form.schedule_id || !form.schedule_date || !form.treatment_ids.length"
                    variant="secondary"
                    class="w-36"
                    :class="{ 'bg-[#1E4F4F] text-white hover:bg-[#3E7F7B]/25': form.schedule_id && form.schedule_date && form.treatment_ids.length }"
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