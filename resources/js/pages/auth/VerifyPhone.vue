<script setup lang="ts">
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();

const form = useForm({
    otp: ''
});

const submit = () => {
    form.post(route('phone.verify'));
};
</script>

<template>
    <AuthLayout title="Verify Phone Number" description="Please verify your phone number by checking the OTP we logged for you.">
        <Head title="Phone Verification" />

        <div v-if="status === 'verification-otp-sent'" class="mb-4 text-center text-sm font-medium text-green-600">
            A new verification OTP has been logged for the phone number you provided during registration.
        </div>

        <form @submit.prevent="submit" class="space-y-6 text-center">
            <input type="text" name="otp" v-model="form.otp" placeholder="Enter OTP" class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" maxlength="6" />
            <Button :disabled="form.processing" variant="secondary">
                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                Verify
            </Button>
            <p class="text-sm text-gray-600">Didn't receive the OTP? <TextLink :href="route('phone.verification.send')" class="text-blue-600">Request a new one</TextLink></p>

            <TextLink :href="route('logout')" method="post" as="button" class="mx-auto block text-sm"> Log out </TextLink>
        </form>
    </AuthLayout>
</template>