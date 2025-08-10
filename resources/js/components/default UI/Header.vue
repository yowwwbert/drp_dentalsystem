<script setup lang="ts">
import { ref } from 'vue'
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
const isMobileMenuOpen = ref(false)

const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value
}
</script>

<template>
    <header class="fixed top-0 left-0 right-0 z-50 w-full mb-0 text-sm bg-white/20 backdrop-blur shadow-md">
        <nav class="flex items-center justify-between gap-4 w-full px-4 sm:px-6 lg:px-8 py-3">
            <!-- Logo -->
            <div class="flex-shrink-0 ml-4">
                <img src="/images/DRP.png" alt="DRP Logo" class="w-32 h-32" />
            </div>

            <!-- Desktop Navigation -->
            <div class="hidden xl:flex items-center justify-between gap-5 ml-auto mr-10">
                <Link
                    :href="route('home')"
                    class="inline-block px-7 py-3 text-lg font-medium leading-normal transition-colors duration-300 rounded-full hover:bg-[#1E4F4F] hover:text-white"
                    :class="route().current('home') ? 'bg-[#1E4F4F] text-white border-2 border-white' : 'text-[#1E4F4F] border-2 border-transparent'"
                >
                    Home
                </Link>
                <Link
                    :href="route('about')"
                    class="inline-block px-7 py-3 text-lg font-medium leading-normal transition-colors duration-300 rounded-full hover:bg-[#1E4F4F] hover:text-white"
                    :class="route().current('about') ? 'bg-[#1E4F4F] text-white border-2 border-white' : 'text-[#1E4F4F] border-2 border-transparent'"
                >
                    About Us
                </Link>
                <Link
                    :href="route('branches')"
                    class="inline-block px-7 py-3 text-lg font-medium leading-normal transition-colors duration-300 rounded-full hover:bg-[#1E4F4F] hover:text-white"
                    :class="route().current('branches') ? 'bg-[#1E4F4F] text-white border-2 border-white' : 'text-[#1E4F4F] border-2 border-transparent'"
                >
                    Branches
                </Link>
                <Link
                    :href="route('services')"
                    class="inline-block px-7 py-3 text-lg font-medium leading-normal transition-colors duration-300 rounded-full hover:bg-[#1E4F4F] hover:text-white"
                    :class="route().current('services') ? 'bg-[#1E4F4F] text-white border-2 border-white' : 'text-[#1E4F4F] border-2 border-transparent'"
                >
                    Services
                </Link>
                <Link
                    :href="route('contact')"
                    class="inline-block px-7 py-3 text-lg font-medium leading-normal transition-colors duration-300 rounded-full hover:bg-[#1E4F4F] hover:text-white"
                    :class="route().current('contact') ? 'bg-[#1E4F4F] text-white border-2 border-white' : 'text-[#1E4F4F] border-2 border-transparent'"
                >
                    Contact Us
                </Link>
                <Link
                    :href="route('login')"
                    class="inline-block rounded-full border border-transparent px-7 py-3 text-lg leading-normal text-white bg-[#1E4F4F] hover:bg-[#163c3c] hover:text-white"
                >
                    Book Appointment
                </Link>
            </div>

            <!-- Mobile Menu Button (Hamburger) -->
            <div class="xl:hidden flex items-center mr-4">
                <button @click="toggleMobileMenu" class="text-[#1E4F4F] focus:outline-none p-2 rounded-full hover:bg-[#1E4F4F] hover:text-white transition-colors duration-300">
                    <svg
                        class="w-8 h-8"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            :class="{ 'hidden': isMobileMenuOpen, 'inline-flex': !isMobileMenuOpen }"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"
                        ></path>
                        <path
                            :class="{ 'hidden': !isMobileMenuOpen, 'inline-flex': isMobileMenuOpen }"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        ></path>
                    </svg>
                </button>
            </div>
        </nav>

        <!-- Mobile Navigation Menu -->
        <div
            v-show="isMobileMenuOpen"
            class="xl:hidden absolute top-full left-0 w-full bg-white shadow-lg py-4 transition-max-height duration-500 ease-in-out overflow-hidden"
            :class="isMobileMenuOpen ? 'max-h-screen' : 'max-h-0'"
        >
            <div class="flex flex-col items-center space-y-4 px-4">
                <Link
                    :href="route('home')"
                    class="w-full text-center py-2 text-lg font-medium text-[#1E4F4F] hover:bg-[#1E4F4F] hover:text-white rounded-full"
                    :class="route().current('home') ? 'bg-[#1E4F4F] text-white' : ''"
                    @click="toggleMobileMenu"
                >
                    Home
                </Link>
                <Link
                    :href="route('about')"
                    class="w-full text-center py-2 text-lg font-medium text-[#1E4F4F] hover:bg-[#1E4F4F] hover:text-white rounded-full"
                    :class="route().current('about') ? 'bg-[#1E4F4F] text-white' : ''"
                    @click="toggleMobileMenu"
                >
                    About Us
                </Link>
                <Link
                    :href="route('branches')"
                    class="w-full text-center py-2 text-lg font-medium text-[#1E4F4F] hover:bg-[#1E4F4F] hover:text-white rounded-full"
                    :class="route().current('branches') ? 'bg-[#1E4F4F] text-white' : ''"
                    @click="toggleMobileMenu"
                >
                    Branches
                </Link>
                <Link
                    :href="route('services')"
                    class="w-full text-center py-2 text-lg font-medium text-[#1E4F4F] hover:bg-[#1E4F4F] hover:text-white rounded-full"
                    :class="route().current('services') ? 'bg-[#1E4F4F] text-white' : ''"
                    @click="toggleMobileMenu"
                >
                    Services
                </Link>
                <Link
                    :href="route('contact')"
                    class="w-full text-center py-2 text-lg font-medium text-[#1E4F4F] hover:bg-[#1E4F4F] hover:text-white rounded-full"
                    :class="route().current('contact') ? 'bg-[#1E4F4F] text-white' : ''"
                    @click="toggleMobileMenu"
                >
                    Contact Us
                </Link>
                <Link
                    :href="route('login')"
                    class="w-full text-center py-2 text-lg font-medium text-white bg-[#1E4F4F] hover:bg-[#163c3c] rounded-full"
                    @click="toggleMobileMenu"
                >
                    Book Appointment
                </Link>
            </div>
        </div>
        <div class="w-full h-px bg-gray-300 shadow-sm"></div>
    </header>
</template>
