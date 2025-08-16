<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Dental Records', href: '/dental-records' },
];

const searchQuery = ref('');
const selectedTreatmentType = ref('All Treatments');
const selectedDateRange = ref('All Time');

// Sample dental records data - in real app, this would come from API
const dentalRecords = ref([
    {
        id: 1,
        date: '2024-01-15',
        treatmentType: 'Dental Cleaning',
        dentist: 'Dr. John Doe',
        branch: 'Main Branch',
        description: 'Professional dental cleaning and scaling. Removed plaque and tartar buildup. Patient showed good oral hygiene practices.',
        procedures: ['Scaling', 'Polishing', 'Fluoride Treatment'],
        cost: 1500,
        status: 'Completed',
        nextVisit: '2024-04-15',
        notes: 'Patient should continue with daily flossing and regular brushing.'
    },
    {
        id: 2,
        date: '2024-01-10',
        treatmentType: 'Cavity Filling',
        dentist: 'Dr. Jane Smith',
        branch: 'Main Branch',
        description: 'Filled cavity on tooth #14 (upper left first molar) with composite material. Patient reported sensitivity resolved.',
        procedures: ['Cavity Preparation', 'Composite Filling', 'Bite Adjustment'],
        cost: 2500,
        status: 'Completed',
        nextVisit: '2024-02-10',
        notes: 'Avoid hard foods on treated tooth for 24 hours. Monitor for any sensitivity.'
    },
    {
        id: 3,
        date: '2023-12-20',
        treatmentType: 'Dental Checkup',
        dentist: 'Dr. John Doe',
        branch: 'Main Branch',
        description: 'Comprehensive oral examination. X-rays taken. No new cavities found. Gum health is good.',
        procedures: ['Oral Examination', 'X-Ray Imaging', 'Gum Assessment'],
        cost: 800,
        status: 'Completed',
        nextVisit: '2024-01-20',
        notes: 'Continue with current oral hygiene routine. Schedule cleaning appointment.'
    },
    {
        id: 4,
        date: '2023-11-15',
        treatmentType: 'Root Canal Treatment',
        dentist: 'Dr. Michael Johnson',
        branch: 'Main Branch',
        description: 'Completed root canal therapy on tooth #19 (lower left first molar). Patient tolerated procedure well.',
        procedures: ['Root Canal Therapy', 'Temporary Filling', 'Post-Op Instructions'],
        cost: 8000,
        status: 'Completed',
        nextVisit: '2024-02-15',
        notes: 'Return for permanent crown placement. Avoid chewing on treated tooth until crown is placed.'
    }
]);

const treatmentTypes = ['All Treatments', 'Dental Cleaning', 'Cavity Filling', 'Dental Checkup', 'Root Canal Treatment', 'Tooth Extraction', 'Dental Crown'];
const dateRanges = ['All Time', 'Last 30 Days', 'Last 3 Months', 'Last 6 Months', 'Last Year'];

const filteredRecords = computed(() => {
    let filtered = dentalRecords.value;
    
    if (searchQuery.value) {
        filtered = filtered.filter(record =>
            record.treatmentType.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            record.dentist.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            record.description.toLowerCase().includes(searchQuery.value.toLowerCase())
        );
    }
    
    if (selectedTreatmentType.value !== 'All Treatments') {
        filtered = filtered.filter(record => record.treatmentType === selectedTreatmentType.value);
    }
    
    if (selectedDateRange.value !== 'All Time') {
        const today = new Date();
        let cutoffDate = new Date();
        
        switch (selectedDateRange.value) {
            case 'Last 30 Days':
                cutoffDate.setDate(today.getDate() - 30);
                break;
            case 'Last 3 Months':
                cutoffDate.setMonth(today.getMonth() - 3);
                break;
            case 'Last 6 Months':
                cutoffDate.setMonth(today.getMonth() - 6);
                break;
            case 'Last Year':
                cutoffDate.setFullYear(today.getFullYear() - 1);
                break;
        }
        
        filtered = filtered.filter(record => new Date(record.date) >= cutoffDate);
    }
    
    return filtered.sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime());
});

const handleSearch = () => {};
const handleTreatmentTypeFilter = (type: string) => { selectedTreatmentType.value = type; };
const handleDateRangeFilter = (range: string) => { selectedDateRange.value = range; };

const getStatusColor = (status: string) => {
    switch (status) {
        case 'Completed': return 'bg-green-100 text-green-800';
        case 'In Progress': return 'bg-blue-100 text-blue-800';
        case 'Scheduled': return 'bg-yellow-100 text-yellow-800';
        case 'Cancelled': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
</script>

<template>
    <Head title="Dental Records" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">My Dental Records</h1>
            </div>

            <!-- Search and Filter Section -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex flex-col sm:flex-row gap-4 mb-6">
                    <div class="flex-1">
                        <input
                            v-model="searchQuery"
                            @input="handleSearch"
                            type="text"
                            placeholder="Search treatments, dentists, or descriptions..."
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                        />
                    </div>
                    <div class="flex gap-2">
                        <select
                            v-model="selectedTreatmentType"
                            @change="handleTreatmentTypeFilter"
                            class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                        >
                            <option v-for="type in treatmentTypes" :key="type" :value="type">
                                {{ type }}
                            </option>
                        </select>
                        <select
                            v-model="selectedDateRange"
                            @change="handleDateRangeFilter"
                            class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                        >
                            <option v-for="range in dateRanges" :key="range" :value="range">
                                {{ range }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Dental Records List -->
                <div class="space-y-4">
                    <div
                        v-for="record in filteredRecords"
                        :key="record.id"
                        class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow duration-200"
                    >
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">{{ record.treatmentType }}</h3>
                                <p class="text-sm text-gray-500">{{ formatDate(record.date) }} • {{ record.dentist }} • {{ record.branch }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span :class="`px-3 py-1 rounded-full text-xs font-medium ${getStatusColor(record.status)}`">
                                    {{ record.status }}
                                </span>
                                <span class="text-lg font-bold text-green-600">₱{{ record.cost.toLocaleString() }}</span>
                            </div>
                        </div>
                        
                        <p class="text-gray-700 mb-4">{{ record.description }}</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 mb-2">Procedures Performed:</h4>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="procedure in record.procedures"
                                        :key="procedure"
                                        class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs"
                                    >
                                        {{ procedure }}
                                    </span>
                                </div>
                            </div>
                            
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 mb-2">Next Visit:</h4>
                                <p class="text-gray-600">{{ record.nextVisit ? formatDate(record.nextVisit) : 'Not scheduled' }}</p>
                            </div>
                        </div>
                        
                        <div v-if="record.notes" class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                            <h4 class="text-sm font-medium text-blue-800 mb-1">Notes:</h4>
                            <p class="text-blue-700 text-sm">{{ record.notes }}</p>
                        </div>
                    </div>
                </div>

                <!-- No Results Message -->
                <div v-if="filteredRecords.length === 0" class="text-center py-12">
                    <div class="text-gray-500 text-lg">No dental records found matching your criteria.</div>
                    <div class="text-gray-400 text-sm mt-2">Try adjusting your search or filters.</div>
                </div>
            </div>

            <!-- Summary Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Total Treatments</h3>
                    <p class="text-3xl font-bold text-darkGreen-900">{{ dentalRecords.length }}</p>
                    <p class="text-sm text-gray-600">Completed procedures</p>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Total Spent</h3>
                    <p class="text-3xl font-bold text-green-600">₱{{ dentalRecords.reduce((sum, record) => sum + record.cost, 0).toLocaleString() }}</p>
                    <p class="text-sm text-gray-600">On dental treatments</p>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Last Visit</h3>
                    <p class="text-3xl font-bold text-blue-600">{{ dentalRecords.length > 0 ? formatDate(dentalRecords[0].date) : 'N/A' }}</p>
                    <p class="text-sm text-gray-600">Most recent treatment</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
