<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import Header from '../components/Header.vue'
import Footer from '../components/Footer.vue'

interface PageProps {
  auth: {
    user: {
      id: number
      name: string
      email: string
      // Add more fields if needed
    } | null
  }
  [key: string]: unknown // ✅ Required for Inertia compatibility
}

const page = usePage<PageProps>()
</script>

<template>
  <Head title="DRP">
    <link rel="preconnect" href="https://rsms.me/" />
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
  </Head>

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
    <Header />

    <div class="duration-750 starting:opacity-0 flex w-full items-center justify-center opacity-100 transition-opacity lg:grow">
      <main class="block w-full flex-col-reverse overflow-hidden rounded-lg lg:max-w-7xl lg:flex-row">
        <h1 class="text-[#1E4F4F] font-bold text-5xl">
          Where affordable dentistry, <br />
          meets exceptional quality.
        </h1>
        <p class="text-[#000000] py-3 font-medium text-lg">
          Welcome to DRP Dental Clinic, book your appointment now!
        </p>
        <Link
          :href="route('login')"
          class="inline-block border-b-2 border-transparent px-5 py-1.5 font-medium rounded-2xl bg-[#1E4F4F] text-white"
        >
          Book Appointment
        </Link>
      </main>
    </div>

    <div class="h-14.5 hidden lg:block"></div>
    
  </div>
  <Footer />
</template>
