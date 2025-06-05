<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>

    <Head title="DRP">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div class="flex min-h-screen flex-col p-4 items-center text-[#1b1b18] dark:text-[#EDEDEC] lg:justify-center lg:p-2"
        :style="{
            backgroundImage: 'linear-gradient(to right, rgba(255, 255, 255, 1), rgba(255, 255, 255, 0)), url(\'/images/DRPBACKGROUND.jpg\')',
            backgroundPosition: 'top',
            backgroundSize: 'cover',
            backgroundRepeat: 'no-repeat'
        }">
       <header class="not-has-[nav]:hidden mb-6 w-full text-sm lg:max-w-7xl">
    <nav class="flex items-center justify-between gap-4">
        <Link v-if="$page.props.auth.user" :href="route('dashboard')"
              class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] transition-colors duration-300 hover:decoration-[#1E4F4F] hover:underline hover:decoration-2 hover:underline-offset-4 dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]">
            Dashboard
        </Link>
        <template v-else>
            <div><img src="images/DRP.png" alt=""></div>
            <div class="flex items-center justify-between gap-5">
                <Link
  :href="route('login')"
  class="inline-block border-b-2 border-transparent px-5 py-1.5 text-base font-medium leading-normal text-[#1E4F4F] transition-colors duration-300
         hover:bg-gradient-to-t hover:from-[#ffffff50] hover:to-[#ffffff00] hover:text-white"
>
  Home
</Link>

                <Link :href="route('login')"
                      class="inline-block border-b-2 border-transparent px-5 py-1.5 text-base font-medium leading-normal text-[#1E4F4F] transition-colors duration-300
         hover:bg-gradient-to-t hover:from-[#ffffff50] hover:to-[#ffffff00] hover:text-white"
>
                    About Us
                </Link>
                <Link :href="route('branches')"
                      class="inline-block border-b-2 border-transparent px-5 py-1.5 text-base font-medium leading-normal text-[#1E4F4F] transition-colors duration-300
         hover:bg-gradient-to-t hover:from-[#ffffff50] hover:to-[#ffffff00] hover:text-white"
>
                    Branches
                </Link>
                <Link :href="route('login')"
                      class="inline-block border-b-2 border-transparent px-5 py-1.5 text-base font-medium leading-normal text-[#1E4F4F] transition-colors duration-300
         hover:bg-gradient-to-t hover:from-[#ffffff50] hover:to-[#ffffff00] hover:text-white"
>
                    Services
                </Link>
                <Link :href="route('login')"
                      class="inline-block border-b-2 border-transparent px-5 py-1.5 text-base font-medium leading-normal text-[#1E4F4F] transition-colors duration-300
         hover:bg-gradient-to-t hover:from-[#ffffff50] hover:to-[#ffffff00] hover:text-white"
>
                    Contact Us
                </Link>
                <Link :href="route('login')"
                      class="inline-block rounded-2xl border border-transparent px-5 py-1.5 text-base leading-normal text-[#1b1b18] dark:bg-[#1E4F4F] dark:text-[#EDEDEC]">
                    Log in
                </Link>
            </div>
        </template>
    </nav>
</header>
        <div
            class="duration-750 starting:opacity-0 flex w-full items-center justify-center opacity-100 transition-opacity lg:grow">
            <main
                class="block w-full flex-col-reverse overflow-hidden rounded-lg lg:max-w-7xl lg:flex-row">
                <h1 class="text-[#1E4F4F] font-bold text-5xl">Where affordable dentistry, <br /> meets exceptional quality.</h1>
                <p class="text-[#000000] py-3 font-medium text-lg">Welcome to DRP Dental Clinic, book your appointment now!</p>
                <Link :href="route('login')" class="inline-block border-b-2 border-transparent px-5 py-1.5 font-medium rounded-2xl
               bg-[#1E4F4F] text-[#FFFFF] ">
                    Book Appointment
                </Link>
            </main>
        </div>
        <div class="h-14.5 hidden lg:block"></div>
    </div>
</template>
