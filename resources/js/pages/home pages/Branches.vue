<script setup lang="ts">
import Header from '../../components/default UI/Header.vue'
import Footer from '../../components/default UI/Footer.vue'
import { Link } from '@inertiajs/vue3'

const branches = [
    {
        name: 'ALABANG BRANCH',
        image: '/images/drpalabang.jpg',
        description: 'DRP Dental Clinic - Alabang is their first branch, established last January 2024.',
        map: 'https://www.google.com/maps/search/Alabang+329+National+Road+Alabang+Muntinlupa',
        appointment: route('appointment'),
        color: 'bg-white text-teal-900 border-2 border-teal-800',
        mapColor: 'text-teal-800',
        button: 'bg-teal-800 text-white hover:bg-teal-600',
        mapButton: 'text-teal-800 font-semibold hover:text-teal-500',
    },
    {
        name: 'MANILA BRANCH',
        image: '/images/drpmanila.jpg',
        description: 'DRP Dental Clinic - Manila is their second branch, established last July 2024.',
        map: 'https://www.google.com/maps/search/3588+P.+Sanchez+St,+Sampaloc,+Manila,+Metro+Manila',
        appointment: route('appointment'),
        color: 'bg-teal-800 text-white',
        mapColor: 'text-white',
        button: 'bg-white text-teal-800 hover:bg-teal-500 hover:text-white',
        mapButton: 'text-white font-semibold hover:text-teal-300',
    },
    {
        name: 'TAGUIG BRANCH',
        image: '/images/TaguigDRP.png',
        description: 'DRP Dental Clinic - Taguig is their third branch, established last February 2025.',
        map: 'https://maps.app.goo.gl/gRLDtEyXNymc851U9',
        appointment: route('appointment'),
        color: 'bg-white text-teal-900 border-2 border-teal-800',
        mapColor: 'text-teal-800',
        button: 'bg-teal-800 text-white hover:bg-teal-600',
        mapButton: 'text-teal-800 font-semibold hover:text-teal-500',
    },
]
</script>

<template>
    ` <Header />
    <div
        class="flex min-h-screen flex-col p-4 items-center text-[#1b1b18] dark:text-[#EDEDEC] lg:justify-center lg:p-2"
        :style="{
        backgroundImage:
            'linear-gradient(to right, rgba(255, 255, 255, 1), rgba(255, 255, 255, 0)), url(\'/images/DRPBACKGROUND.jpg\')',
        backgroundPosition: 'top',
        backgroundSize: 'cover',
        backgroundRepeat: 'no-repeat',
        }"
    >
        
        <div class="flex flex-col items-center px-6 py-12 w-full">
        <div class="flex flex-col md:flex-row justify-center items-stretch gap-8 w-full max-w-7xl">
            <div
            v-for="(branch, idx) in branches"
            :key="branch.name"
            :class="['flex flex-col rounded-lg p-6 md:p-8 w-full md:w-1/3 shadow-lg', branch.color]"
            >
            <img
                :src="branch.image"
                :alt="branch.name"
                class="w-full h-[250px] md:h-[350px] object-cover rounded-lg"
            />
            <div class="text-center mt-4 flex-1 flex flex-col justify-between">
                <h1 class="text-2xl font-bold mb-2">{{ branch.name }}</h1>
                <p class="mt-2 text-lg">{{ branch.description }}</p>
                <div class="mt-4">
                <a
                    :href="branch.map"
                    target="_blank"
                    rel="noopener noreferrer"
                    :class="branch.mapButton"
                >
                    See in Google Maps
                </a>
                </div>
                <div class="mt-4">
                <Link
                    :href="branch.appointment"
                    :class="'inline-block py-2 px-6 rounded-full font-semibold transition ' + branch.button"
                >
                    Make an Appointment Here
                </Link>
                </div>
            </div>
            </div>
        </div>
        </div>
        
    </div>
    <Footer />
    </template>

<style scoped>
.shadow-lg {
    box-shadow: 0 4px 24px 0 rgba(30, 79, 79, 0.10), 0 1.5px 4px 0 rgba(30, 79, 79, 0.10);
}
</style>
