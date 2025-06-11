<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import Header from '../../components/default UI/Header.vue'
import Footer from '../../components/default UI/Footer.vue'

const about = [
    {
        title: 'ABOUT US',
        description: 'Discover our journey and meet the passionate team behind our success.'
    },
    {
        title: 'OUR HISTORY',
        description: 'Founded in 2024, our mission has always been to provide the best dental care with a focus on innovation, empathy, and excellence. From humble beginnings to becoming a trusted name in the industry, our journey has been one of growth and dedication to our community.',
        image: '/images/drpmanila.jpg'
    }
]

const team = [
    {
        name: 'Dr. Dave Gatdula',
        image: '/images/drpmanila.jpg',
        description: 'Mentum eros quis neque malesuada. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce elementum eros quis neque malesuada. Lorem ipsum dolor sit amet, consectetur adipiscing elit.'
    },
    {
        name: 'Dr. Robin Bacani',
        image: '/images/drpalabang.jpg',
        description: 'Mentum eros quis neque malesuada. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce elementum eros quis neque malesuada. Lorem ipsum dolor sit amet, consectetur adipiscing elit.'
    },
    {
        name: 'Dr. Patrick Nidoy',
        image: '/images/TaguigDRP.png',
        description: 'Mentum eros quis neque malesuada. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce elementum eros quis neque malesuada. Lorem ipsum dolor sit amet, consectetur adipiscing elit.'
    }
]

const banner = 'MEET THE OWNERS'

</script>

<template>
  <Head title="About Us">
    <link rel="preconnect" href="https://rsms.me/" />
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
  </Head>

  <Header />

  <div
    class="flex min-h-screen flex-col p-4 items-center text-[#1b1b18] dark:text-[#EDEDEC] lg:justify-center lg:p-2"
    :style="{
      backgroundImage: 'linear-gradient(to right, rgba(255, 255, 255, 1), rgba(255, 255, 255, 0)), url(\'/images/DRPBACKGROUND.jpg\')',
      backgroundPosition: 'top',
      backgroundSize: 'cover',
      backgroundRepeat: 'no-repeat',
    }"
  >
    <!-- About Us -->
    <section class="w-full flex flex-col items-center mt-12 mb-4 pt-50">
      <h1 class="text-4xl font-bold mb-4 text-center text-teal-900">{{ about[0].title }}</h1>
      <p class="text-xl text-center max-w-2xl mb-8 text-teal-900">
        {{ about[0].description }}
      </p>
      <hr class="w-full border-t border-teal-900 my-8" />
    </section>

    <!-- History -->
    <section class="w-full max-w-6xl bg-white border-4 border-teal-700 rounded-xl shadow-lg p-6 mt-12">
      <div class="flex flex-col lg:flex-row items-center justify-center w-full gap-12">
        <!-- Image -->
        <div class="bg-teal-600 rounded-lg flex items-center justify-center w-100 h-80 mb-6 lg:mb-0 shadow-lg">
          <img :src="about[1].image" alt="DRP Manila Branch" class="w-100 h-80 rounded-lg object-cover" />
        </div>
        <!-- Desc -->
        <div class="flex-1 max-w-xl text-lg text-teal-900">
          <h2 class="text-2xl font-bold mb-4 text-center text-teal-900">{{ about[1].title }}</h2>
          <p>
            {{ about[1].description }}
          </p>
        </div>
      </div>
    </section>   

    <!-- Team -->
    <section class="py-12 w-full text-center">
        <!-- Banner -->
        <div class="w-full bg-teal-900 py-8 flex items-center justify-center mb-6 mt-12 rounded-lg">
        <h2 class="text-3xl font-bold text-white tracking-wide">{{ banner }}</h2>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 gap-y-12 px-6">
            <div v-for="(member, index) in team" :key="index" class="bg-white rounded-lg p-6 shadow-md text-center border-4 border-teal-700">
                <img :src="member.image" :alt="member.name" class="w-36 h-36 mx-auto rounded-full object-cover mb-4" />
                <h3 class="text-xl font-semibold mb-3 text-teal-900">{{ member.name }}</h3>
                <p class="text-sm text-teal-900 text-justify">
                    {{ member.description }}
                </p>
            </div>
        </div>
    </section>
  </div>

  <Footer />
</template>