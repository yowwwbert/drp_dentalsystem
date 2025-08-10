<script lang="ts" setup>
import { Label } from '@/components/ui/label';
import AppointmentLayout from '@/layouts/form/AppointmentLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { LoaderCircle } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { route } from 'ziggy-js';

interface Dentist {
    user_id: string;
    first_name: string;
    last_name: string;
}

interface Treatment {
    treatment_id: string;
    treatment_name: string;
}

const props = defineProps<{
    branch_id?: string;
}>();

const dentists = ref<Dentist[]>([]);
const treatments = ref<Treatment[]>([]);
const errorMessage = ref<string>('');

const form = useForm({
    branch_id: props.branch_id || sessionStorage.getItem('selected_branch_id') || '',
    dentist_id: '',
    treatment_id: '',
});

onMounted(async () => {
    try {
        const treatmentResponse = await axios.get(route('appointment.treatments'));
        treatments.value = treatmentResponse.data || [];
        if (!treatments.value.length) {
            errorMessage.value = 'No treatments available. Please try again later.';
        }

        if (form.branch_id) {
            const dentistResponse = await axios.get(route('appointment.dentists', {
                branch_id: form.branch_id
            }));
            dentists.value = dentistResponse.data || [];
            if (!dentists.value.length) {
                errorMessage.value = 'No dentists available for this branch.';
            }
        } else {
            errorMessage.value = 'No branch selected. Please go back and select a branch.';
        }
    } catch (error) {
        console.error('Error fetching data:', error);
        errorMessage.value = 'Failed to load data. Please try again.';
    }
});

const submitForm = () => {
    console.log('Submitting form with data:', form);
    form.post(route('dentist.store'), {
        preserveState: true,
        preserveScroll: true,
        forceFormData: true, // Ensure no query parameters are appended
        onSuccess: () => {
            console.log('Form submitted successfully');
        },
        onError: (errors) => {
            console.error('Form submission errors:', errors);
            errorMessage.value = 'Please correct the errors in the form: ' + Object.values(errors).join(', ');
        },
    });
};
</script>

<template>
    <AppointmentLayout
        title="Select Dentist & Treatment"
        description="Please select your preferred dentist and treatment for the appointment."
        :current-step="2"
    >
        <Head title="Book an Appointment" />
        <form @submit.prevent="submitForm" class="flex flex-col gap-6">
            <div v-if="errorMessage" class="text-red-500 text-sm p-2 bg-red-50 rounded-lg">
                {{ errorMessage }}
            </div>
            <div>
                <Label for="dentist" class="text-sm font-medium">Dentist</Label>
                <select
                    id="dentist"
                    v-model="form.dentist_id"
                    class="border rounded-lg p-2 w-full dark:bg-gray-800 dark:border-gray-600"
                    :disabled="!dentists.length"
                >
                    <option value="" disabled>Select a dentist</option>
                    <option v-for="dentist in dentists" :key="dentist.user_id" :value="dentist.user_id">
                        Dr. {{ dentist.last_name }}, {{ dentist.first_name }}
                    </option>
                </select>
                <span v-if="form.errors.dentist_id" class="text-red-500 text-sm">
                    {{ form.errors.dentist_id }}
                </span>
            </div>
            <div>
                <Label for="treatment" class="text-sm font-medium">Treatment</Label>
                <select
                    id="treatment"
                    v-model="form.treatment_id"
                    class="border rounded-lg p-2 w-full dark:bg-gray-800 dark:border-gray-600"
                    :disabled="!treatments.length"
                >
                    <option value="" disabled>Select a treatment</option>
                    <option v-for="treatment in treatments" :key="treatment.treatment_id" :value="treatment.treatment_id">
                        {{ treatment.treatment_name }}
                    </option>
                </select>
                <span v-if="form.errors.treatment_id" class="text-red-500 text-sm">
                    {{ form.errors.treatment_id }}
                </span>
            </div>
            <div class="flex justify-end mt-4">
                <Button
                    type="submit"
                    :disabled="form.processing || !form.dentist_id || !form.treatment_id"
                    variant="secondary"
                    class="w-36"
                    :class="{ 'bg-[#3E7F7B] text-white hover:bg-[#3E7F7B]/50': form.dentist_id && form.treatment_id }"
                >
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    <span v-else>Next</span>
                </Button>
            </div>
        </form>
    </AppointmentLayout>
</template>