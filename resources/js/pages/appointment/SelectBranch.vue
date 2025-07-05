<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppointmentLayout from '@/layouts/form/AppointmentLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { route } from 'ziggy-js';
import TextLink from '@/components/TextLink.vue';
import InputError from '@/components/InputError.vue';
import { ref, onMounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

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
    branch_id: '', // Changed from selectedBranch to branch_id
});

// Debug branch_id changes
watch(() => form.branch_id, (newValue) => {
    console.log('Selected Branch ID:', newValue);
});

// Fetch branches on component mount
onMounted(async () => {
    try {
        const response = await axios.get(route('appointment.branches'));
        console.log('Fetched branches:', response.data);
        branches.value = response.data.map((branch: Branch) => ({
            ...branch,
            branch_id: String(branch.branch_id), // Ensure branch_id is a string
        }));
    } catch (error) {
        console.error('Error fetching branches:', error);
    }
});

// Submit form
const submit = () => {
    console.log('Submitting form with data:', form.data());
    form.post(route('branch.store'), {
        onSuccess: () => {
            console.log('Branch submitted successfully');
            router.visit(route('appointments.dentist'));
        },
        onFinish: () => {
            console.log('Form submission finished', form.errors);
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
        <Head title="Select Branch" />
        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid grid-cols-1 gap-4">
                <div class="grid gap-2">
                    <Label for="selectedBranch">Select Branch</Label>
                    <select
                        id="selectedBranch"
                        v-model="form.branch_id"
                        class="w-full border rounded p-2"
                        :disabled="form.processing || branches.length === 0"
                    >
                        <option value="" disabled>Select a branch</option>
                        <option v-for="branch in branches" :value="branch.branch_id" :key="branch.branch_id">
                            {{ branch.branch_name }} ({{ branch.branch_address }})
                        </option>
                    </select>
                    <InputError :message="form.errors.branch_id" />
                </div>
            </div>

            <Button type="submit" :disabled="form.processing || !form.branch_id" variant="secondary">
                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                Next
            </Button>
        </form>

        <TextLink :href="route('logout')" method="post" as="button" class="mx-auto block text-sm mt-4">
            Log out
        </TextLink>
    </AppointmentLayout>
</template>