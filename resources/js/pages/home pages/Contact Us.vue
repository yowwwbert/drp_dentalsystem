<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import Header from '../../components/default UI/Header.vue'
import Footer from '../../components/default UI/Footer.vue'
import { computed } from 'vue'

const contact = {
    title: 'CONTACT US',
    description: 'Reach out to us. Lorem ipsum dolor sit amet consectetur adipisicing elit. Magnam unde asperiores explicabo animi accusantium! Repellat, temporibus debitis! Nostrum accusamus amet suscipit, doloribus vitae cupiditate alias dolorum? Corrupti necessitatibus a quam!'
}

const contactInfo = [
  {
    title: 'drpdentalclinic03@gmail.com',
    subtitle: 'Have a project in mind?\nSend a message.',
    bgColor: 'bg-teal-50',
    iconBg: 'bg-teal-100',
    // You can add an icon SVG string or component here if you want
  },
  {
    title: '(0999) 881 849',
    subtitle: 'We\'re interested in\nworking together!',
    bgColor: 'bg-teal-50',
    iconBg: 'bg-teal-100',
  },
  {
    title: 'Alabang, Manila, Taguig',
    subtitle: 'Would you like to join our growing team?',
    bgColor: 'bg-teal-50',
    iconBg: 'bg-teal-100',
  },
]

const contactImage = '/images/DRPBACKGROUND.jpg'

const contactFormFields = [
  { label: 'FIRST NAME', type: 'text', name: 'firstName', placeholder: 'Enter your first name', row: 1 },
  { label: 'LAST NAME', type: 'text', name: 'lastName', placeholder: 'Enter your last name', row: 1 },
  { label: 'EMAIL ADDRESS', type: 'email', name: 'email', placeholder: 'Enter your email', row: 2 },
  { label: 'PHONE NO', type: 'text', name: 'phone', placeholder: 'Enter your phone number', row: 2 },
  { label: 'MESSAGE', type: 'textarea', name: 'message', placeholder: 'Type your message here', row: 3 },
  {
    type: 'checkbox',
    name: 'terms',
    label: 'I agree to the',
    linkText: 'Terms and conditions',
    linkHref: '#',
    row: 4
  },
  {
    type: 'button',
    buttonText: 'Send Message',
    row: 5
  }
]

const messageField = computed(() => contactFormFields.find(f => f.row === 3))

</script>

<template>
  <Head title="Contact Us">
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

    <!--Contact Us-->  
    <section class="w-full flex flex-col items-center mt-12 mb-4 pt-50">
        <h1 class="text-4xl font-bold mb-4 text-center text-teal-900">{{ contact.title }}</h1>
        <p class="text-xl text-center max-w-2xl mb-8 text-teal-900">
            {{ contact.description }}
        </p>
        <hr class="w-full border-t border-teal-900 my-8" />
    </section>

    <div class="w-full flex flex-col lg:flex-row bg-white bg-opacity-80 rounded-xl shadow-xl overflow-hidden max-w-6xl mx-auto border-4 border-teal-700 lg:h-[600px] mb-12">
      <!-- Image -->
      <div class="w-full lg:w-1/2 h-96 lg:h-full">
        <img :src="contactImage" alt="Dentist" class="object-cover w-full h-full" />
      </div>
      <!-- Contact Form -->
      <div class="w-full lg:w-1/2 p-10 bg-white flex flex-col justify-center">
        <form class="space-y-6">
          <div class="flex flex-col md:flex-row md:space-x-4">
            <div v-for="field in contactFormFields.filter(f => f.row === 1)" :key="field.name" class="flex-1 mb-4 md:mb-0">
              <label class="block text-sm font-medium text-black mb-2">{{ field.label }}</label>
              <input v-if="field.type !== 'textarea'" :type="field.type" :name="field.name" :placeholder="field.placeholder" class="w-full border-b-2 border-gray-300 focus:border-teal-700 outline-none py-2 bg-transparent text-black" />
            </div>
          </div>
          <div class="flex flex-col md:flex-row md:space-x-4">
            <div v-for="field in contactFormFields.filter(f => f.row === 2)" :key="field.name" class="flex-1 mb-4 md:mb-0">
              <label class="block text-sm font-medium text-black mb-2">{{ field.label }}</label>
              <input v-if="field.type !== 'textarea'" :type="field.type" :name="field.name" :placeholder="field.placeholder" class="w-full border-b-2 border-gray-300 focus:border-teal-700 outline-none py-2 bg-transparent text-black" />
            </div>
          </div>
          <div>
            <template v-if="messageField">
              <label class="block text-sm font-medium text-black mb-2">{{ messageField.label }}</label>
              <textarea rows="3" :name="messageField.name" :placeholder="messageField.placeholder" class="w-full border-b-2 border-gray-300 focus:border-teal-700 outline-none py-2 bg-transparent text-black"></textarea>
            </template>
          </div>
          <div v-for="field in contactFormFields.filter(f => f.row === 4)" :key="field.name">
            <div v-if="field.type === 'checkbox'" class="flex items-center">
              <input :id="field.name" type="checkbox" class="h-4 w-4 accent-teal-700 focus:ring-teal-500 border-gray-300 rounded" />
              <label :for="field.name" class="ml-2 block text-sm text-gray-700">
                {{ field.label }}
                <a :href="field.linkHref" class="text-teal-900 underline">{{ field.linkText }}</a>
              </label>
            </div>
          </div>
          <div v-for="field in contactFormFields.filter(f => f.row === 5)" :key="field.buttonText">
            <button
              v-if="field.type === 'button'"
              type="submit"
              class="w-full py-4 rounded-lg font-semibold text-lg transition bg-teal-800 text-white hover:bg-teal-600"
            >
              {{ field.buttonText }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Contact Information -->
    <section class="w-full flex flex-col items-center my-12">
      <h2 class="text-4xl font-bold text-center mb-8 text-teal-900">Stay Connected With Our Contact Information</h2>
      <div class="w-full max-w-6xl grid grid-cols-1 md:grid-cols-3 gap-8 px-4">
        <div v-for="(info, idx) in contactInfo" :key="idx" :class="info.bgColor + ' rounded-xl flex flex-col items-center p-10 shadow-md'">
          <div class="mb-6">
            <span :class="'inline-block ' + info.iconBg + ' rounded-full p-4'"></span>
          </div>
          <div class="font-bold text-xl text-teal-900 mb-2">{{ info.title }}</div>
          <div class="text-black text-center" v-html="info.subtitle.replace(/\\n/g, '<br/>')"></div>
        </div>
      </div>
    </section>
  </div>
  <Footer />
</template>

<style scoped>
::placeholder {
  color: rgb(77, 76, 76); 
  opacity: 0.7;
}

::-moz-placeholder {
  color: rgb(77 76 76); 
  opacity: 0.7;
}

::-ms-input-placeholder {
  color: rgb(77 76 76); 
  opacity: 0.7;
}
</style>