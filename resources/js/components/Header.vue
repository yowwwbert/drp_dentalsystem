<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'

interface PageProps {
    auth: {
        user: {
        id: number
        name: string
        email: string
        } | null
    }
    [key: string]: unknown
}

const page = usePage<PageProps>()
</script>

<template>
    <header class="fixed top-0 left-0 right-0 z-50 w-full not-has-[nav]:hidden mb-0 text-sm bg-white/20 backdrop-blur shadow-md">
        <nav class="flex items-center justify-between gap-4 w-full px-8">
        <Link
            v-if="page.props.auth?.user"
            :href="route('dashboard')"
            class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] transition-colors duration-300 hover:decoration-[#1E4F4F] hover:underline hover:decoration-2 hover:underline-offset-4 dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
        >
            Dashboard
        </Link>
        <template v-else>
            <div ><img src="images/DRP.png" alt="DRP Logo"  class="w-40 h-40 ml-20" /></div>
            <div class="flex items-center justify-between gap-5 mr-10">
            <Link
                :href="route('home')"
                class="inline-block border-b-2 border-transparent px-7 py-3 text-lg font-medium leading-normal text-[#1E4F4F] transition-colors duration-300 rounded-full hover:bg-[#1E4F4F] hover:text-white"
            >
                Home
            </Link>
            <Link
                :href="route('login')"
                class="inline-block border-b-2 border-transparent px-7 py-3 text-lg font-medium leading-normal text-[#1E4F4F] transition-colors duration-300 rounded-full hover:bg-[#1E4F4F] hover:text-white"
            >
                About Us
            </Link>
            <Link
                :href="route('branches')"
                class="inline-block border-b-2 border-transparent px-7 py-3 text-lg font-medium leading-normal text-[#1E4F4F] transition-colors duration-300 rounded-full hover:bg-[#1E4F4F] hover:text-white"
            >
                Branches
            </Link>
            <Link
                :href="route('login')"
                class="inline-block border-b-2 border-transparent px-7 py-3 text-lg font-medium leading-normal text-[#1E4F4F] transition-colors duration-300 rounded-full hover:bg-[#1E4F4F] hover:text-white"
            >
                Services
            </Link>
            <Link
                :href="route('login')"
                class="inline-block border-b-2 border-transparent px-7 py-3 text-lg font-medium leading-normal text-[#1E4F4F] transition-colors duration-300 rounded-full hover:bg-[#1E4F4F] hover:text-white"
            >
                Contact Us
            </Link>
            <Link
                :href="route('login')"
                class="inline-block rounded-full border border-transparent px-7 py-3 text-lg leading-normal text-white bg-[#1E4F4F] hover:bg-[#163c3c] hover:text-white"
            >
                Log in
            </Link>
            </div>
        </template>
        </nav>
        <div class="w-full h-px bg-gray-300 shadow-sm"></div>
    </header>
</template>
