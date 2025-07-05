<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppointmentLayout from '@/layouts/form/AppointmentLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { route } from 'ziggy-js';
import TextLink from '@/components/TextLink.vue';
import InputError from '@/components/InputError.vue';
import { ref, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { templateRef } from '@vueuse/core';

defineProps<{
    branch_id?: string | null;
    branch_name?: string | null;
    branch_address?: string | null;
}>();

const page = usePage();
interface Branch {
    branch_id: string;
    branch_name: string;
    branch_address: string;
}

const branches = ref<Branch[]>([]);
const form = useForm({
    selectedBranch: '',
    selectedDate: '',
    selectedTime: '',
});
</script>

<template>
    <AppointmentLayout
        title="Select Date & Time"
        description="Please select your preferred date and time for the appointment."
        :current-step="2"
    >
        <div class="flex flex-col gap-6">
            <Label for="date" class="text-sm font-medium">Date</Label>
            <InputError v-if="form.errors.selectedDate" :message="form.errors.selectedDate" />
            <Label for="time" class="text-sm font-medium">Time</Label>
            <InputError v-if="form.errors.selectedTime" :message="form.errors.selectedTime" />
        </div>
    </AppointmentLayout>
</template>
