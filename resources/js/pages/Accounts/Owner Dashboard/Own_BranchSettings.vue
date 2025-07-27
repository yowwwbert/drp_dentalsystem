<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Clinic',
        href: '/clinic',
    },
    {
        title: 'Branch Settings',
        href: '/clinic/branches',
    },
];
</script>

<template>
    <Head title="Branch Settings" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">Branch Settings</h1>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Branch Management</h2>
                <p class="text-gray-600">This page will contain the branch settings and management functionality.</p>
            </div>
        </div>
    </AppLayout>
</template> 