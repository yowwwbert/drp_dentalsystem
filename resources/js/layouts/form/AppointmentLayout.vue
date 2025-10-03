<script setup lang="ts">
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { router, usePage } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
    currentStep: number; // Required prop to track current step
}>();

const page = usePage();

// Redirect Patient users
const goToStep = (step: number) => {
    if (step === 1) router.visit(route('appointment'));
    else if (step === 2 && page.props.selectedBranch) router.visit(route('appointments.dentist'));
    else if (step === 3 && page.props.selectedBranch && page.props.selectedDentist && page.props.selectedDate && page.props.selectedTime)
        router.visit(route('appointments.service'));
};
</script>

<template>
    <div class="bg-muted flex min-h-svh flex-col items-center justify-start gap-4 p-8 md:p-8">
        <div class="flex w-full max-w-5xl flex-col gap-5">
            <div class="mb-6 flex w-full max-w-5xl flex-col gap-4">
                <div class="grid grid-cols-[48px_1fr_48px_1fr_48px_1fr_48px_1fr_48px] items-center justify-items-center gap-0 pt-24">
                    <!-- Step 1 -->
                    <div
                        class="flex h-12 w-12 cursor-pointer items-center justify-center rounded-full text-xl font-bold shadow-md transition-all"
                        :class="{
                            'bg-[#1E4F4F] text-white': currentStep > 1,
                            'border-2 border-[#3E7F7B] bg-white text-[#3E7F7B]': currentStep === 1,
                            'bg-gray-300 text-white': currentStep < 1,
                        }"
                        @click="goToStep(1)"
                    >
                        1
                    </div>
                    <div class="h-1 w-full transition-colors duration-300" :class="currentStep > 1 ? 'bg-[#1E4F4F]' : 'bg-gray-300'"></div>
                    <!-- Step 2 -->
                    <div
                        class="flex h-12 w-12 cursor-pointer items-center justify-center rounded-full text-xl font-bold shadow-md transition-all"
                        :class="{
                            'bg-[#1E4F4F] text-white': currentStep > 2,
                            'border-2 border-[#3E7F7B] bg-white text-[#3E7F7B]': currentStep === 2,
                            'bg-gray-300 text-white': currentStep < 2,
                        }"
                        @click="goToStep(2)"
                    >
                        2
                    </div>
                    <div class="h-1 w-full transition-colors duration-300" :class="currentStep > 2 ? 'bg-[#1E4F4F]' : 'bg-gray-300'"></div>
                    <!-- Step 3 -->
                    <div
                        class="flex h-12 w-12 cursor-pointer items-center justify-center rounded-full text-xl font-bold shadow-md transition-all"
                        :class="{
                            'bg-[#1E4F4F] text-white': currentStep > 3,
                            'border-2 border-[#3E7F7B] bg-white text-[#3E7F7B]': currentStep === 3,
                            'bg-gray-300 text-white': currentStep < 3,
                        }"
                        @click="goToStep(3)"
                    >
                        3
                    </div>
                    <div class="h-1 w-full transition-colors duration-300" :class="currentStep > 3 ? 'bg-[#1E4F4F]' : 'bg-gray-300'"></div>
                    <!-- Step 4 -->
                    <div
                        class="flex h-12 w-12 cursor-pointer items-center justify-center rounded-full text-xl font-bold shadow-md transition-all"
                        :class="{
                            'bg-[#1E4F4F] text-white': currentStep > 4,
                            'border-2 border-[#3E7F7B] bg-white text-[#3E7F7B]': currentStep === 4,
                            'bg-gray-300 text-white': currentStep < 4,
                        }"
                        @click="goToStep(4)"
                    >
                        4
                    </div>
                    <div class="h-1 w-full transition-colors duration-300" :class="currentStep > 4 ? 'bg-[#1E4F4F]' : 'bg-gray-300'"></div>
                    <!-- Step 5 -->
                    <div
                        class="flex h-12 w-12 cursor-pointer items-center justify-center rounded-full text-xl font-bold shadow-md transition-all"
                        :class="{
                            'border-2 border-[#3E7F7B] bg-white text-[#3E7F7B]': currentStep === 5,
                            'bg-gray-300 text-white': currentStep < 5,
                        }"
                        @click="goToStep(5)"
                    >
                        5
                    </div>
                </div>
                <!-- Labels aligned under steps -->
                <div class="text-md text-gray-480 grid grid-cols-[48px_1fr_48px_1fr_48px_1fr_48px_1fr_48px] justify-items-center py-0">
                    <span class="text-center whitespace-nowrap">Select Branch</span>
                    <div></div>
                    <span class="text-center whitespace-nowrap">Select Dentist & Treatment</span>
                    <div></div>
                    <span class="text-center whitespace-nowrap">Select Date & Time</span>
                    <div></div>
                    <span class="text-center whitespace-nowrap">Enter Personal Information</span>
                    <div></div>
                    <span class="text-center whitespace-nowrap">Confirm Appointment</span>
                </div>
            </div>

            <div class="flex flex-col gap-6">
                <Card class="rounded-xl">
                    <CardHeader class="px-6 pt-4 pb-0 text-center">
                        <CardTitle class="text-xl">{{ title }}</CardTitle>
                        <CardDescription>{{ description }}</CardDescription>
                    </CardHeader>
                    <CardContent class="px-6 py-4">
                        <slot />
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>