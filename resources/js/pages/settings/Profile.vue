<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

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
    first_name: user.first_name,
    last_name: user.last_name,
    middle_name: user.middle_name,
    suffix: user.suffix,
    birth_date: user.birth_date,
    sex: user.sex,
    occupation: user.occupation,
    religion: user.religion,
    profile_picture: user.profile_picture,
    errors: {},
});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout>

        <Head title="Profile Settings" />
        <SettingsLayout>
            <div class="flex flex-col md:flex-row gap-8">
                <div class="flex-1 w-full">
                    <div class="flex flex-col space-y-6">
                        <HeadingSmall title="Profile Information" description="Update your phone number and email address" />
                        <Label class="text-md font-medium">Personal Details</Label>
                        <form action="">
                            <div class="space-y-6">
                                <div class="flex gap-4">
                                    <div class="flex-1">
                                        <Label for="first_name" class="font-normal">First Name</Label>
                                        <Input id="first_name" class="mt-1 block w-full" v-model="form.first_name" required
                                            autocomplete="given-name" placeholder="First Name" disabled />
                                        <InputError class="mt-2" :message="form.errors.first_name" />
                                    </div>
                                    <div class="flex-1">
                                        <Label for="last_name" class="font-normal">Last Name</Label>
                                        <Input id="last_name" class="mt-1 block w-full" v-model="form.last_name" required
                                            autocomplete="family-name" placeholder="Last Name" disabled />
                                        <InputError class="mt-2" :message="form.errors.last_name" />
                                    </div>
                                </div>
                                <div class="flex gap-4">
                                    <div class="flex-1">
                                        <Label for="middle_name" class="font-normal">Middle Name</Label>
                                        <Input id="middle_name" class="mt-1 block w-full" v-model="form.middle_name" required
                                            autocomplete="additional-name" placeholder="Middle Name" disabled />
                                        <InputError class="mt-2" :message="form.errors.middle_name" />
                                    </div>
                                    <div class="flex-1">
                                        <Label for="suffix" class="font-normal">Suffix</Label>
                                        <Input id="suffix" class="mt-1 block w-full" v-model="form.suffix" required
                                            autocomplete="honorific-suffix" placeholder="Suffix" disabled />
                                        <InputError class="mt-2" :message="form.errors.suffix" />
                                    </div>
                                </div>
                                <div class="flex gap-4">
                                    <div class="flex-1">
                                        <Label for="occupation" class="font-normal">Occupation</Label>
                                        <Input id="occupation" class="mt-1 block w-full" v-model="form.occupation" required
                                            autocomplete="additional-name" placeholder="Occupation" />
                                        <InputError class="mt-2" :message="form.errors.occupation" />
                                    </div>
                                    <div class="flex-1">
                                        <Label for="religion" class="font-normal">Religion</Label>
                                        <Input id="religion" class="mt-1 block w-full" v-model="form.religion" required
                                            autocomplete="honorific-suffix" placeholder="Religion" />
                                        <InputError class="mt-2" :message="form.errors.religion" />
                                    </div>
                                </div>
                            </div>
                        </form>
                        <Label class="text-md font-medium">Contact Details</Label>
                        <form @submit.prevent="submit" class="space-y-6">
                            <div class="grid gap-2">
                                <Label for="phone_number" class="font-normal">Phone Number</Label>
                                <div class="flex items-center gap-2">
                                    <Input id="phone_number" class="mt-1 block w-full" v-model="form.phone_number" required
                                        autocomplete="phone_number" placeholder="+63 9123456789" />
                                    <Link v-if="mustVerifyPhone && user.phone_verified_at"
                                        :href="route('phone-verification.send')" method="post" as="button"
                                        class="text-sm text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current dark:decoration-neutral-500">
                                    Verify
                                    </Link>
                                </div>
                                <InputError class="mt-2" :message="form.errors.phone_number" />
                                <div v-if="mustVerifyPhone && !user.phone_verified_at" class="text-sm text-muted-foreground">
                                    Your phone number is unverified.
                                </div>
                                <div v-if="status === 'phone-verification-sent'"
                                    class="mt-2 text-sm font-medium text-green-600">
                                    A verification code has been sent to your phone number.
                                </div>
                            </div>

                            <div class="grid gap-2">
                                <Label for="email_address" class="font-normal">Email Address</Label>
                                <div class="flex items-center gap-2">
                                    <Input id="email_address" type="email" class="mt-1 block w-full"
                                        v-model="form.email_address" required autocomplete="username"
                                        placeholder="Email address" />
                                    <Link v-if="mustVerifyEmail && !user.email_verified_at" :href="route('verification.send')"
                                        method="post" as="button"
                                        class="text-sm text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current dark:decoration-neutral-500">
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

                                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
                                    leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                                    <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">Saved.</p>
                                </Transition>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="flex flex-col items-center md:w-1/3 w-full mb-8 md:mb-0 shadow-md">
                    <div class="w-32 h-32 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden mb-4 mt-12 border-2 border-[#3E7F7B]">
                        <span class="text-4xl text-gray-400"><img :src="user.profile_picture" alt="Profile Picture" class="w-full h-full object-cover" /></span>
                    </div>
                    
                    <!-- You can add upload/change button here if needed -->
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>