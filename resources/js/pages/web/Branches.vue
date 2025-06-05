<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Branches" />
    <div class="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 dark:bg-[#0a0a0a] lg:p-8">
        <header class="not-has-[nav]:hidden mb-6 w-full max-w-[335px] text-sm lg:max-w-4xl">
            <nav class="flex items-center justify-between gap-4">
                <template v-if="$page.props.auth.user">
                    <Link :href="route('dashboard')" class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                    >
                        Dashboard
                    </Link>
                </template>
                
                <template v-else>
                    <h1 class="inline-block text-[#fffff]">DRP Dental Clinic</h1>
                    <Link :href="route('login')" class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                    >
                        Log in
                    </Link>
                </template>
                
                <Link :href="route('home')" class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                >
                    Home
                </Link>
            </nav>
        </header>
        <main>
            <h1 class="text-2xl">Branches</h1>
            <p>List of branches in the dental system.</p>
            <!-- Add branch list logic here -->
        </main>
    </div>
</template>