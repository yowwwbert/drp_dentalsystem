<script setup lang="ts">
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'

const email = ref('')
const handleSubscription = (event: Event) => {
  event.preventDefault()
  if (email.value) {
    alert('Thank you for subscribing!')
    email.value = ''
  }
}

const links = [
  { path: '/', label: 'Home' },
  { path: '/branches', label: 'Branches' },
  { path: '/about', label: 'About Us' },
  { path: '/services', label: 'Services' },
  { path: '/contact', label: 'Contact' },
  { path: '/appointment', label: 'Book an Appointment' },
]

const branches = [
  {
    location: 'Alabang',
    address: 'Alabang 329 National Road Alabang Muntinlupa',
    schedule: 'Mon - Thurs\n10AM - 6PM',
    instagram: '@drpdentalclinic',
    facebook: 'DRP Dental Clinic - Alabang',
    phone: '(0999) 881 849',
    email: 'drpdentalclinic03@gmail.com',
  },
  {
    location: 'Manila',
    address: '3588 P. Sanchez St, Sampaloc, Manila, Metro Manila',
    schedule: 'Mon - Fri\n9AM - 5PM',
    instagram: '@drpmanila03',
    facebook: 'DRP Dental Clinic - Manila',
    phone: '(0962) 344 9494',
    email: 'drpmanila03@gmail.com',
  },
]
</script>

<template>
  <footer class="w-full bg-[#1E4F4F] shadow-md text-white pt-10 pb-6 px-0">
    <div class="flex flex-col md:flex-row md:justify-between gap-10 w-full">
      <!-- Left Section -->
      <div class="flex-1 space-y-8 px-6 md:px-20">
        <h1 class="text-2xl md:text-3xl font-bold tracking-wide mb-3">Want to hear from us?</h1>
        <p class="mt-2 text-gray-200">
          Subscribe to our newsletter to receive updates, tips, and exclusive offers directly to your inbox.
        </p>
        <form class="mt-4 space-y-4" @submit="handleSubscription">
          <input
            type="email"
            placeholder="Enter your email address"
            v-model="email"
            required
            class="w-full p-2 text-white  rounded-md border-2 border-white focus:border-teal-600 focus:outline-none"
          />
          <button
            type="submit"
            class="mt-2 w-full bg-teal-600 hover:bg-white hover:text-teal-600 text-white font-bold py-2 px-4 rounded"
          >
            Subscribe
          </button>
        </form>
      </div>
      <!-- Right Section (Branches) -->
      <div class="flex-1 flex flex-col md:flex-row md:justify-between gap-6 px-6 md:px-20">
        <div v-for="(branch, index) in branches" :key="branch.location" class="w-full md:w-1/2 space-y-4">
          <h3 class="text-xl font-bold">{{ branch.location }}</h3>
          <div class="mt-2 space-y-1">
            <h4 class="font-semibold">Address</h4>
            <p>{{ branch.address }}</p>
          </div>
          <div class="mt-2 space-y-1">
            <h4 class="font-semibold">Schedule</h4>
            <p v-for="line in branch.schedule.split('\n')" :key="line">{{ line }}</p>
          </div>
          <div class="mt-2 space-y-1">
            <h4 class="font-semibold">Follow us</h4>
            <p>
              <span class="mr-2">📸</span>{{ branch.instagram }}
            </p>
            <p>
              <span class="mr-2">📘</span>{{ branch.facebook }}
            </p>
          </div>
          <div class="mt-2">
            <h4 class="font-semibold">Contact</h4>
            <p>{{ branch.phone }}</p>
            <p>{{ branch.email }}</p>
          </div>
          <hr v-if="index === 0" class="md:hidden border-gray-400 my-4" />
        </div>
      </div>
    </div>
  </footer>
</template> 