<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type SharedData, type User } from '@/types';

interface Props {
    mustVerifyEmail: boolean;
    mustVerifyPhone: boolean;
    status?: string;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: '/settings/profile',
    },
];

const page = usePage<SharedData>();
const user = page.props.auth.user as User;

const form = useForm({
    user_id: user.user_id,
    phone_number: user.phone_number,
    email_address: user.email_address,
    phone_verified_at: user.phone_verified_at,
    email_verified_at: user.email_verified_at,
    errors: {},
});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Profile settings" />
        <h1>User id: {{ user.phone_verified_at }}</h1>
        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall title="Profile information" description="Update your phone number and email address" />

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="phone_number">Phone Number</Label>
                        <div class="flex items-center gap-2">
                            <Input
                                id="phone_number"
                                class="mt-1 block w-full"
                                v-model="form.phone_number"
                                required
                                autocomplete="phone_number"
                                placeholder="+63 9123456789"
                            />
                            <Link
                                v-if="mustVerifyPhone && user.phone_verified_at"
                                :href="route('phone-verification.send')"
                                method="post"
                                as="button"
                                class="text-sm text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current dark:decoration-neutral-500"
                            >
                                Verify
                            </Link>
                        </div>
                        <InputError class="mt-2" :message="form.errors.phone_number" />
                        <div v-if="mustVerifyPhone && !user.phone_verified_at" class="text-sm text-muted-foreground">
                            Your phone number is unverified.
                        </div>
                        <div v-if="status === 'phone-verification-sent'" class="mt-2 text-sm font-medium text-green-600">
                            A verification code has been sent to your phone number.
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="email_address">Email Address</Label>
                        <div class="flex items-center gap-2">
                            <Input
                                id="email_address"
                                type="email"
                                class="mt-1 block w-full"
                                v-model="form.email_address"
                                required
                                autocomplete="username"
                                placeholder="Email address"
                            />
                            <Link
                                v-if="mustVerifyEmail && !user.email_verified_at"
                                :href="route('verification.send')"
                                method="post"
                                as="button"
                                class="text-sm text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current dark:decoration-neutral-500"
                            >
                                Verify
                            </Link>
                        </div>
                        <InputError class="mt-2" :message="form.errors.email_address" />
                        <div v-if="mustVerifyEmail && !user.email_verified_at" class="text-sm text-muted-foreground">
                            Your email address is unverified.
                        </div>
                        <div v-if="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
                            A new verification link has been sent to your email address.
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">Save</Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">Saved.</p>
                        </Transition>
                    </div>
                </form>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>