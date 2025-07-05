<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Link, router } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

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
    else if (step === 3 && page.props.selectedBranch && page.props.selectedDentist && page.props.selectedDate && page.props.selectedTime) router.visit(route('appointments.service'));
};
</script>

<template>
    <div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-muted p-6 md:p-10">
        <div class="flex w-full max-w-md flex-col gap-6">
            <!-- Step Indicators -->
            <div class="flex w-full max-w-md flex-col gap-4 mb-6">
                <div class="flex justify-between items-center">
                    <div
                        class="w-12 h-12 flex items-center justify-center rounded-full text-white text-xl font-bold shadow-md cursor-pointer"
                        :class="{
                            'bg-green-500': currentStep > 1,
                            'bg-red-500': currentStep === 1,
                            'bg-gray-300': currentStep < 1,
                        }"
                        @click="goToStep(1)"
                    >
                        1
                    </div>
                    <div class="flex-1 h-1 bg-gray-300" :class="{ 'bg-green-500': currentStep > 1 }"></div>
                    <div
                        class="w-12 h-12 flex items-center justify-center rounded-full text-white text-xl font-bold shadow-md cursor-pointer"
                        :class="{
                            'bg-green-500': currentStep > 2,
                            'bg-blue-500': currentStep === 2,
                            'bg-gray-300': currentStep < 2,
                        }"
                        @click="goToStep(2)"
                    >
                        2
                    </div>
                    <div class="flex-1 h-1 bg-gray-300" :class="{ 'bg-green-500': currentStep > 2 }"></div>
                    <div
                        class="w-12 h-12 flex items-center justify-center rounded-full text-white text-xl font-bold shadow-md cursor-pointer"
                        :class="{
                            'bg-blue-500': currentStep === 3,
                            'bg-gray-300': currentStep < 3,
                        }"
                        @click="goToStep(3)"
                    >
                        3
                    </div>
                </div>
                <div class="flex justify-between text-sm text-gray-600">
                    <span>Select Branch</span>
                    <span>Select Dentist & Date</span>
                    <span>Select Service</span>
                </div>
            </div>

            <div class="flex flex-col gap-6">
                <Card class="rounded-xl">
                    <CardHeader class="px-10 pb-0 pt-8 text-center">
                        <CardTitle class="text-xl">{{ title }}</CardTitle>
                        <CardDescription>{{ description }}</CardDescription>
                    </CardHeader>
                    <CardContent class="px-10 py-8">
                        <slot />
                    </CardContent>
                </Card>
            </div>

        </div>
    </div>
</template>
