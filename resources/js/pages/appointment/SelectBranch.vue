<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import AppointmentLayout from '@/layouts/form/AppointmentLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { route } from 'ziggy-js';
import InputError from '@/components/InputError.vue';
import { ref, onMounted } from 'vue';
import axios from 'axios';

interface Branch {
    branch_id: string;
    branch_name: string;
    branch_address: string;
    branch_image?: string;
}

const branches = ref<Branch[]>([]);
const form = useForm({
    branch_id: '',
});

const selectedBranchId = ref<string | null>(null);

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
    form.branch_id = branchId;
    selectedBranchId.value = branchId;
};

const submit = () => {
    form.post(route('branch.store'), {
        preserveState: true,
        preserveScroll: true,
        forceFormData: true, // Ensure no query parameters are appended
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
                <div v-if="branches.length === 0" class="text-gray-500 text-center">
                    No branches available.
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="branch in branches"
                        :key="branch.branch_id"
                        class="border rounded-lg p-4 shadow-sm hover:shadow-md hover:border-[#3E7F7B] transition-shadow dark:bg-gray-800"
                        :class="{ 'bg-[#3E7F7B] dark:bg-[#3E7F7B]/20': selectedBranchId === branch.branch_id }"
                        role="button"
                        tabindex="0"
                        :aria-selected="selectedBranchId === branch.branch_id"
                        @click="selectBranch(branch.branch_id)"
                        @keydown.enter="selectBranch(branch.branch_id)"
                        @keydown.space.prevent="selectBranch(branch.branch_id)"
                    >
                        <h3
                            class="text-lg font-semibold text-gray-900 dark:text-gray-100"
                            :class="{ 'text-white': selectedBranchId === branch.branch_id }"
                        >
                            {{ branch.branch_name }}
                        </h3>
                        <img
                            :src="branch.branch_image"
                            alt="Branch image"
                            class="w-full h-32 object-cover rounded-lg mb-2 border border-gray-200 dark:border-gray-900"
                        />
                        <p
                            class="text-sm text-gray-800 dark:text-gray-200"
                            :class="{ 'text-white': selectedBranchId === branch.branch_id }"
                        >
                            {{ branch.branch_address }}
                        </p>
                        <Button
                            type="button"
                            variant="outline"
                            class="mt-4 w-full hover:bg-[#3E7F7B]/50"
                            :class="{ 'bg-[#C0D4D3] border-[#3E7F7B] text-[#1E4F4F]': selectedBranchId === branch.branch_id }"
                            @click="selectBranch(branch.branch_id)"
                        >
                            {{ selectedBranchId === branch.branch_id ? 'Selected' : 'Select Branch' }}
                        </Button>
                    </div>
                </div>
                <InputError :message="form.errors.branch_id" class="mt-2" />
            </div>
            <div class="flex justify-end mt-4">
                <Button
                    type="submit"
                    :disabled="form.processing || !form.branch_id"
                    variant="secondary"
                    class="w-36"
                    :class="{ 'bg-[#3E7F7B] text-white hover:bg-[#3E7F7B]/50': form.branch_id }"
                >
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    <span v-else>Next</span>
                </Button>
            </div>
        </form>
    </AppointmentLayout>
</template>