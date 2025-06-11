<script setup lang="ts">
import Header from '../../components/default UI/Header.vue'
import Footer from '../../components/default UI/Footer.vue'
import { Swiper, SwiperSlide } from 'swiper/vue'
import 'swiper/css'
import 'swiper/css/navigation'
import 'swiper/css/autoplay'
import { Navigation, Autoplay } from 'swiper/modules'

const modules = [Navigation, Autoplay]
const services = [
  {
    id: 1,
    image: '/images/DRPBACKGROUND.jpg',
    icon: '/images/clean.png',
    title: 'Cleaning',
    description: 'Professional dental cleaning to maintain oral health and prevent cavities.',
    color: 'bg-[#1E4F4F]',
    textColor: 'text-white',
  },
  {
    id: 2,
    image: '/images/DRPBACKGROUND.jpg',
    icon: '/images/whiten.png',
    title: 'Whitening',
    description: 'Enhance the brightness of your smile with safe whitening procedures.',
    color: 'bg-[#4D807A]',
    textColor: 'text-white',
  },
  {
    id: 3,
    image: '/images/DRPBACKGROUND.jpg',
    icon: '/images/filling.png',
    title: 'Filling',
    description: 'Restore teeth damaged by decay with durable and aesthetic fillings.',
    color: 'bg-[#1E4F4F]',
    textColor: 'text-white',
  },
  {
    id: 4,
    image: '/images/DRPBACKGROUND.jpg',
    icon: '/images/extraction.png',
    title: 'Tooth Extraction',
    description: 'Safe removal of damaged or problematic teeth with minimal discomfort.',
    color: 'bg-[#4D807A]',
    textColor: 'text-white',
  },
  {
    id: 5,
    image: '/images/DRPBACKGROUND.jpg',
    icon: '/images/implant.png',
    title: 'Implants',
    description: 'Replace missing teeth with durable and natural-looking dental implants.',
    color: 'bg-[#1E4F4F]',
    textColor: 'text-white',
  },
  {
    id: 6,
    image: '/images/DRPBACKGROUND.jpg',
    icon: '/images/ortho.png',
    title: 'Orthodontics',
    description: 'Align and straighten teeth for improved functionality and aesthetics.',
    color: 'bg-[#4D807A]',
    textColor: 'text-white',
  },
  {
    id: 7,
    image: '/images/DRPBACKGROUND.jpg',
    icon: '/images/root_canal.png',
    title: 'Root Canal',
    description: 'Preserve damaged teeth by treating infections within the tooth pulp.',
    color: 'bg-[#1E4F4F]',
    textColor: 'text-white',
  },
  {
    id: 8,
    image: '/images/DRPBACKGROUND.jpg',
    icon: '/images/cosmetic.png',
    title: 'Cosmetic Dentistry',
    description: 'Enhance your smile with advanced cosmetic dental procedures.',
    color: 'bg-[#4D807A]',
    textColor: 'text-white',
  },
]
</script>

<template>
  <div
    class="flex min-h-screen flex-col p-4 items-center text-[#1b1b18] dark:text-[#EDEDEC] lg:justify-center lg:p-2 overflow-visible"
    :style="{
      backgroundImage:
      'linear-gradient(to right, rgba(255, 255, 255, 1), rgba(255, 255, 255, 0)), url(\'/images/DRPBACKGROUND.jpg\')',
      backgroundPosition: 'top',
      backgroundSize: 'cover',
      backgroundRepeat: 'no-repeat',
    }"
  >
    <Header />

    <div class="py-12 w-full mt-24 relative overflow-visible">
      <div class="max-w-[85rem] mx-auto px-10 relative">
        <h1 class="text-center text-6xl font-bold uppercase tracking-wide text-[#1E4F4F] mb-12">
          Our Services
        </h1>
        <div
          id="swiper-button-prev-custom"
          class="absolute top-1/2 transform -translate-y-1/2 z-20 flex items-center justify-center cursor-pointer text-[#1E4F4F] hover:text-white transition-colors duration-300"
        >
          &#x2039;
        </div>
        <div
          id="swiper-button-next-custom"
          class="absolute top-1/2 transform -translate-y-1/2 z-20 flex items-center justify-center cursor-pointer text-[#1E4F4F] hover:text-white transition-colors duration-300"
        >
          &#x203A;
        </div>

        <swiper
          :slides-per-view="4"
          :space-between="20"
          :navigation="{ prevEl: '#swiper-button-prev-custom', nextEl: '#swiper-button-next-custom' }"
          :modules="modules"
          class="mySwiper"
          :loop="true"
          :autoplay="{
            delay: 3000,
            disableOnInteraction: false,
          }"
          :breakpoints="{
            320: { slidesPerView: 1, spaceBetween: 10 },
            640: { slidesPerView: 2, spaceBetween: 20 },
            768: { slidesPerView: 3, spaceBetween: 20 },
            1024: { slidesPerView: 4, spaceBetween: 20 },
          }"
        >
          <swiper-slide v-for="service in services" :key="service.id">
            <div
              class="relative bg-white rounded-lg shadow-lg overflow-hidden border-2 border-[#4D807A] h-[450px] flex flex-col"
            >
              <img
                :src="service.image"
                :alt="service.title"
                class="w-full h-48 object-cover"
              />
              <div
                class="absolute top-[147px] left-1/2 transform -translate-x-1/2 bg-white rounded-full p-3 shadow-md z-10 border-4 border-white flex items-center justify-center"
                :class="service.color"
                style="width: 90px; height: 90px;"
              >
                <img :src="service.icon" :alt="service.title + ' icon'" class="w-16 h-16 object-contain" />
              </div>
              <div class="pt-16 px-4 pb-4 text-center flex-1 flex flex-col justify-between">
                <h3 class="text-xl font-semibold text-gray-800 mb-2">{{ service.title }}</h3>
                <p class="text-gray-600 text-sm mb-2">{{ service.description }}</p>
              </div>
              <div class="h-2 w-full" :class="service.color"></div>
            </div>
          </swiper-slide>
        </swiper>
      </div>
    </div>

    
  </div>
  <Footer />
</template>

<style scoped>
.mySwiper {
  /* Removed padding */
}

.swiper-button-next, .swiper-button-prev {
  /* Removed original Swiper button styles */
  display: none; /* Hide default Swiper buttons */
}

/* New styles for custom buttons */
#swiper-button-prev-custom,
#swiper-button-next-custom {
  font-size: 150px; /* Increased font size to make arrows bigger */
  font-weight: bold;
  /* Removed padding-top */
}

#swiper-button-prev-custom {
  left: -20px;
}

#swiper-button-next-custom {
  right: -20px;
}
</style>