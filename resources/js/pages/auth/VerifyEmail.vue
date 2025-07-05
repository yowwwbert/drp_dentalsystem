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

const form = useForm({});

const resend = () => {
    form.post(route('verification.send'), {
        onSuccess: () => form.reset(),
        onError: (errors) => console.error('Resend errors:', errors),
    });
};
</script>

<template>
    <AuthLayout
        title="Verify Email"
        description="Please verify your email address by clicking on the link we emailed to you."
    >
        <Head title="Email Verification" />

        <div v-if="status === 'verification-link-sent'" class="mb-4 text-center text-sm font-medium text-green-600">
            A new verification link has been sent to the email address you provided.
        </div>

        <form v-if="has_email" @submit.prevent="resend" class="space-y-6 text-center">
            <Button :disabled="form.processing || !has_email" variant="secondary">
                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                Resend verification email
            </Button>
        </form>

        <div v-if="has_phone" class="text-sm text-gray-600 text-center mt-4">
            
            <TextLink :href="route('phone.verification.notice')" class="text-blue-600 hover:underline">
                Verify phone number instead
            </TextLink>
        </div>

        <TextLink :href="route('logout')" method="post" as="button" class="mx-auto block text-sm mt-4">
            Log out
        </TextLink>
    </AuthLayout>
</template>