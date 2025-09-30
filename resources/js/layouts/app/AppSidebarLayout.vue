<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <div class="flex h-screen">
        <!-- Custom Sidebar -->
        <AppSidebar />
        
        <!-- Content area -->
        <div class="flex-1 px-8 bg-gray-200 overflow-auto dark:bg-gray-900">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </div>
    </div>
</template>
