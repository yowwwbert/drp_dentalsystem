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
        title: 'Patient Records',
        href: '/records/patients',
    },
];
</script>

<template>
    <Head title="Patient Records" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">Patient Records</h1>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Patient Management</h2>
                <p class="text-gray-600">This page will contain the receptionist's patient records and management functionality.</p>
            </div>
        </div>
    </AppLayout>
</template> 