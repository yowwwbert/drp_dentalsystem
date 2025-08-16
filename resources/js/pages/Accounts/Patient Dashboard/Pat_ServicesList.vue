<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Services', href: '/services' },
];

const searchQuery = ref('');
const selectedCategory = ref('All Categories');
const selectedBranch = ref('All Branches');

// Sample services data - in real app, this would come from API
const services = ref([
    {
        id: 1,
        name: 'Dental Cleaning',
        category: 'Preventive',
        description: 'Professional dental cleaning and scaling to remove plaque and tartar',
        price: 1500,
        duration: '45 minutes',
        branch: 'Main Branch',
        available: true
    },
    {
        id: 2,
        name: 'Dental Checkup',
        category: 'Preventive',
        description: 'Comprehensive oral examination and consultation',
        price: 800,
        duration: '30 minutes',
        branch: 'Main Branch',
        available: true
    },
    {
        id: 3,
        name: 'Cavity Filling',
        category: 'Restorative',
        description: 'Tooth-colored composite filling for cavities',
        price: 2500,
        duration: '1 hour',
        branch: 'Main Branch',
        available: true
    },
    {
        id: 4,
        name: 'Root Canal Treatment',
        category: 'Endodontic',
        description: 'Complete root canal therapy for infected teeth',
        price: 8000,
        duration: '2-3 hours',
        branch: 'Main Branch',
        available: true
    },
    {
        id: 5,
        name: 'Tooth Extraction',
        category: 'Surgical',
        description: 'Simple tooth extraction procedure',
        price: 3000,
        duration: '1 hour',
        branch: 'Main Branch',
        available: true
    },
    {
        id: 6,
        name: 'Dental Crown',
        category: 'Restorative',
        description: 'Porcelain crown restoration for damaged teeth',
        price: 12000,
        duration: '2 hours',
        branch: 'Main Branch',
        available: true
    },
    {
        id: 7,
        name: 'Teeth Whitening',
        category: 'Cosmetic',
        description: 'Professional in-office teeth whitening treatment',
        price: 5000,
        duration: '1 hour',
        branch: 'Main Branch',
        available: true
    },
    {
        id: 8,
        name: 'Dental Implant',
        category: 'Surgical',
        description: 'Complete dental implant placement and restoration',
        price: 45000,
        duration: '3-4 hours',
        branch: 'Main Branch',
        available: true
    }
]);

const categories = ['All Categories', 'Preventive', 'Restorative', 'Endodontic', 'Surgical', 'Cosmetic'];
const branches = ['All Branches', 'Main Branch', 'North Branch', 'South Branch'];

const filteredServices = computed(() => {
    let filtered = services.value;
    
    if (searchQuery.value) {
        filtered = filtered.filter(service =>
            service.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            service.description.toLowerCase().includes(searchQuery.value.toLowerCase())
        );
    }
    
    if (selectedCategory.value !== 'All Categories') {
        filtered = filtered.filter(service => service.category === selectedCategory.value);
    }
    
    if (selectedBranch.value !== 'All Branches') {
        filtered = filtered.filter(service => service.branch === selectedBranch.value);
    }
    
    return filtered;
});

const handleSearch = () => {};
const handleCategoryFilter = (category: string) => { selectedCategory.value = category; };
const handleBranchFilter = (branch: string) => { selectedBranch.value = branch; };

const getCategoryColor = (category: string) => {
    switch (category) {
        case 'Preventive': return 'bg-blue-100 text-blue-800';
        case 'Restorative': return 'bg-green-100 text-green-800';
        case 'Endodontic': return 'bg-purple-100 text-purple-800';
        case 'Surgical': return 'bg-red-100 text-red-800';
        case 'Cosmetic': return 'bg-pink-100 text-pink-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};
</script>

<template>
    <Head title="Available Services" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">Available Services</h1>
            </div>

            <!-- Search and Filter Section -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex flex-col sm:flex-row gap-4 mb-6">
                    <div class="flex-1">
                        <input
                            v-model="searchQuery"
                            @input="handleSearch"
                            type="text"
                            placeholder="Search services..."
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                        />
                    </div>
                    <div class="flex gap-2">
                        <select
                            v-model="selectedCategory"
                            @change="handleCategoryFilter"
                            class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                        >
                            <option v-for="category in categories" :key="category" :value="category">
                                {{ category }}
                            </option>
                        </select>
                        <select
                            v-model="selectedBranch"
                            @change="handleBranchFilter"
                            class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                        >
                            <option v-for="branch in branches" :key="branch" :value="branch">
                                {{ branch }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Services Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="service in filteredServices"
                        :key="service.id"
                        class="bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200"
                    >
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-3">
                                <h3 class="text-lg font-semibold text-gray-900">{{ service.name }}</h3>
                                <span :class="`px-2 py-1 rounded-full text-xs font-medium ${getCategoryColor(service.category)}`">
                                    {{ service.category }}
                                </span>
                            </div>
                            
                            <p class="text-gray-600 text-sm mb-4 line-clamp-3">{{ service.description }}</p>
                            
                            <div class="space-y-2 mb-4">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Duration:</span>
                                    <span class="text-gray-700 font-medium">{{ service.duration }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Branch:</span>
                                    <span class="text-gray-700 font-medium">{{ service.branch }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Status:</span>
                                    <span :class="`font-medium ${service.available ? 'text-green-600' : 'text-red-600'}`">
                                        {{ service.available ? 'Available' : 'Not Available' }}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="border-t border-gray-200 pt-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-2xl font-bold text-green-600">₱{{ service.price.toLocaleString() }}</span>
                                    <button
                                        class="bg-darkGreen-900 text-white px-4 py-2 rounded-lg hover:bg-darkGreen-800 transition-colors duration-200 text-sm font-medium"
                                        :disabled="!service.available"
                                    >
                                        Book Appointment
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- No Results Message -->
                <div v-if="filteredServices.length === 0" class="text-center py-12">
                    <div class="text-gray-500 text-lg">No services found matching your criteria.</div>
                    <div class="text-gray-400 text-sm mt-2">Try adjusting your search or filters.</div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
