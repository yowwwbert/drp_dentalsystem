<script setup lang="ts">
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
    has_email?: boolean;
    has_phone?: boolean;
    phone_number?: string | null;
    email_address?: string | null;
}>();
const form = useForm({
    otp: ''
});

const submit = () => {
    form.post(route('phone.verify.store'), {
        onSuccess: () => form.reset(),
        onError: (errors) => console.error('OTP verification errors:', errors),
    });
};

const resend = () => {
    form.post(route('phone.verification.notification'), {
        onSuccess: () => form.reset(),
        onError: (errors) => console.error('Resend errors:', errors),
    });
};
</script>

<template>
    <AuthLayout title="Verify Phone Number" description="Please verify your phone number by checking the OTP we logged for you.">
        <Head title="Phone Verification" />

        <div v-if="status === 'phone-verification-code-sent'" class="mb-4 text-center text-sm font-medium text-green-600">
            A new verification OTP has been logged for the phone number you provided during registration.
        </div>
        <div v-if="form.errors.otp" class="mb-4 text-center text-sm font-medium text-red-600">
            {{ form.errors.otp }}
        </div>

        <form @submit.prevent="submit" class="space-y-6 text-center">
            <div class="grid gap-2">
                <input
                    type="text"
                    name="otp"
                    v-model="form.otp"
                    placeholder="Enter OTP"
                    class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    maxlength="6"
                />
            </div>
            <Button type="submit" :disabled="form.processing" variant="secondary">
                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                Verify
            </Button>
            <p class="text-sm text-gray-600">
                Didn't receive the OTP?
                <button type="button" @click="resend" class="text-blue-600 hover:underline bg-transparent border-none p-0 cursor-pointer">
                    Request a new one
                </button>
            </p>
            <div v-if="has_email" class="text-sm text-gray-600 text-center mt-4">
                <TextLink :href="route('verification.notice')" class="text-blue-600 hover:underline">
                    Verify email address instead
                </TextLink>
            </div>

            <TextLink :href="route('logout')" method="post" as="button" class="mx-auto block text-sm">
                Log out
            </TextLink>
        </form>
    </AuthLayout>
</template>