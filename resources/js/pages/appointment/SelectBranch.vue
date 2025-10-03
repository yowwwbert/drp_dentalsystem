<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import AppointmentLayout from '@/layouts/form/AppointmentLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { MapPin, CalendarDays, Clock, LoaderCircle } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { route } from 'ziggy-js';

interface Branch {
    branch_id: string;
    branch_name: string;
    branch_address: string;
    branch_image?: string;
    opening_time?: string;
    closing_time?: string;
    operating_days?: string[] | string;
}

const branches = ref<Branch[]>([]);
const form = useForm({
    branch_id: '',
    branch_name: '',
    branch_address: '',
});

const selectedBranchId = ref<string | null>(null);

// Function to format time to 12-hour format in Philippine time (e.g., "10:00:00" -> "10:00 AM")
const formatTime = (time?: string): string => {
    if (!time) return 'N/A';
    try {
        // Parse time as local time (no UTC conversion)
        const date = new Date(`1970-01-01T${time}`);
        return date.toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit', hour12: true });
    } catch (error) {
        console.error('Error formatting time:', time, error);
        return 'N/A';
    }
};

// Function to format operating days with ranges for 3+ consecutive days
const formatOperatingDays = (days?: string[] | string): string => {
    if (!days) return 'N/A';

    // Convert string to array if necessary (e.g., "Monday,Saturday,Wednesday" or JSON string)
    let daysArray: string[];
    if (typeof days === 'string') {
        try {
            daysArray = JSON.parse(days);
            if (!Array.isArray(daysArray)) {
                daysArray = days.split(',').map((day) => day.trim());
            }
        } catch {
            daysArray = days.split(',').map((day) => day.trim());
        }
    } else if (Array.isArray(days)) {
        daysArray = days;
    } else {
        return 'N/A';
    }

    if (!daysArray.length) return 'N/A';

    const dayOrder: { [key: string]: number } = {
        Monday: 1,
        Tuesday: 2,
        Wednesday: 3,
        Thursday: 4,
        Friday: 5,
        Saturday: 6,
        Sunday: 7,
    };
    const shortDays: { [key: string]: string } = {
        Monday: 'Mon',
        Tuesday: 'Tue',
        Wednesday: 'Wed',
        Thursday: 'Thu',
        Friday: 'Fri',
        Saturday: 'Sat',
        Sunday: 'Sun',
    };

    // Normalize and filter valid days
    const validDays = daysArray
        .map((day) => day.charAt(0).toUpperCase() + day.slice(1).toLowerCase())
        .filter((day) => dayOrder[day]);
    if (!validDays.length) return 'N/A';

    const sortedDays = validDays.sort((a, b) => dayOrder[a] - dayOrder[b]);
    const ranges: string[] = [];
    let start = sortedDays[0];
    let startIndex = dayOrder[start];
    let count = 1; // Track number of consecutive days

    for (let i = 1; i <= sortedDays.length; i++) {
        const current = sortedDays[i];
        const currentIndex = current ? dayOrder[current] : null;

        // If not consecutive or end of array, finalize range or single days
        if (!current || currentIndex !== dayOrder[sortedDays[i - 1]] + 1) {
            if (count >= 3) {
                // Range for 3 or more consecutive days
                ranges.push(`${shortDays[start]}-${shortDays[sortedDays[i - 1]]}`);
            } else {
                // Push individual days for 1 or 2 consecutive days
                for (let j = i - count; j < i; j++) {
                    ranges.push(shortDays[sortedDays[j]]);
                }
            }
            start = current;
            startIndex = currentIndex || startIndex;
            count = 1; // Reset count for new range
        } else {
            count++; // Increment count for consecutive day
        }
    }

    return ranges.join(', ') || 'N/A';
};

onMounted(async () => {
    try {
        const response = await axios.get(route('appointment.branches'));
        branches.value = response.data.map((branch: Branch) => ({
            ...branch,
            branch_id: String(branch.branch_id),
            branch_image: branch.branch_image || '/images/DRP.png',
        }));
    } catch (error) {
        console.error('Error fetching branches:', error);
    }
});

const selectBranch = (branchId: string) => {
    const selectedBranch = branches.value.find((branch) => branch.branch_id === branchId);
    form.branch_id = branchId;
    form.branch_name = selectedBranch ? selectedBranch.branch_name : '';
    form.branch_address = selectedBranch ? selectedBranch.branch_address : '';
    selectedBranchId.value = branchId;
};

const submit = () => {
    form.post(route('branch.store'), {
        preserveState: true,
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            console.log('Branch submitted successfully');
        },
        onError: (errors) => {
            console.error('Form submission errors:', errors);
        },
    });
};
</script>

<template>
    <AppointmentLayout
        title="Select Branch"
        description="Please select a branch to proceed with your appointment."
        :currentStep="1"
    >
        <Head title="Book an Appointment" />
        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-4">
                <div v-if="branches.length === 0" class="text-center text-gray-500">
                    No branches available.
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3" style="align-items: start;">
                    <div
                        v-for="branch in branches"
                        :key="branch.branch_id"
                        class="group flex min-h-[250px] flex-col rounded-lg border p-4 shadow-sm transition-all hover:bg-[#1E4F4F]/10 hover:shadow-xl dark:bg-gray-800"
                        :class="{ 'border-2 border-[#1E4F4F] shadow-xl scale-105': selectedBranchId === branch.branch_id }"
                        role="button"
                        tabindex="0"
                        :aria-selected="selectedBranchId === branch.branch_id"
                        @click="selectBranch(branch.branch_id)"
                        @keydown.enter="selectBranch(branch.branch_id)"
                        @keydown.space.prevent="selectBranch(branch.branch_id)"
                    >
                        <img
                            :src="branch.branch_image"
                            alt="Branch image"
                            class="mb-3 h-32 w-full rounded-lg border border-gray-200 object-cover dark:border-gray-900"
                        />
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ branch.branch_name }}
                        </h2>
                        <!-- Details: Expands the card downwards when shown -->
                        <div
                            class="max-h-0 overflow-hidden transition-all duration-300 group-hover:max-h-40"
                            :class="{ '!max-h-40': selectedBranchId === branch.branch_id }"
                        >
                            <div class="mt-2 border-l-2 border-[#1E4F4F] bg-white p-4 shadow-lg dark:bg-gray-800 dark:border-l-[#1E4F4F]">
                                <p class="flex items-center gap-2 text-sm font-medium text-gray-800 dark:text-gray-200">
                                    <MapPin class="h-5 w-5 shrink-0 text-[#1E4F4F]" />
                                    <span class="flex-1">{{ branch.branch_address }}</span>
                                </p>
                                <p class="mt-2 flex items-center gap-2 text-sm text-gray-800 dark:text-gray-200">
                                    <CalendarDays class="h-5 w-5 shrink-0 text-[#1E4F4F]" />
                                    <span class="flex-1">{{ formatOperatingDays(branch.operating_days) }}</span>
                                </p>
                                <p class="mt-2 flex items-center gap-2 text-sm text-gray-800 dark:text-gray-200">
                                    <Clock class="h-5 w-5 shrink-0 text-[#1E4F4F]" />
                                    <span class="flex-1">
                                        {{ formatTime(branch.opening_time) }} - {{ formatTime(branch.closing_time) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            class="mt-4 w-full hover:bg-[#1E4F4F]/50"
                            :class="{ 'border-[#1E4F4F] bg-[#C0D4D3] text-[#1E4F4F]': selectedBranchId === branch.branch_id }"
                            @click="selectBranch(branch.branch_id)"
                        >
                            {{ selectedBranchId === branch.branch_id ? 'Selected' : 'Select Branch' }}
                        </Button>
                    </div>
                </div>
                <InputError :message="form.errors.branch_id" class="mt-2" />
                <InputError :message="form.errors.branch_name" class="mt-2" />
            </div>
            <div class="mt-4 flex justify-end">
                <Button
                    type="submit"
                    :disabled="form.processing || !form.branch_id || !form.branch_name"
                    variant="secondary"
                    class="w-36"
                    :class="{ 'bg-[#1E4F4F] text-white hover:bg-[#1E4F4F]/50': form.branch_id && form.branch_name }"
                >
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    <span v-else>Next</span>
                </Button>
            </div>
        </form>
    </AppointmentLayout>
</template>