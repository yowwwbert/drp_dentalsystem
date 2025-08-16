<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Dental Chart', href: '/dental-chart' },
];

const selectedPatient = ref('');
const selectedDate = ref(new Date().toISOString().split('T')[0]);

// Sample patients data - in real app, this would come from API
const patients = ref([
    { id: 'P001', name: 'John Smith', age: 35, gender: 'Male' },
    { id: 'P002', name: 'Maria Garcia', age: 28, gender: 'Female' },
    { id: 'P003', name: 'Robert Johnson', age: 45, gender: 'Male' },
    { id: 'P004', name: 'Sarah Wilson', age: 32, gender: 'Female' }
]);

// Dental chart data structure
const dentalChart = ref({
    patientId: '',
    patientName: '',
    chartDate: '',
    teeth: {} as Record<string, any>
});

// Initialize teeth data
const initializeTeeth = () => {
    const teethData: Record<string, any> = {};
    
    // Upper teeth (1-16)
    for (let i = 1; i <= 16; i++) {
        teethData[i.toString()] = {
            condition: 'Normal',
            treatment: '',
            notes: '',
            lastUpdated: ''
        };
    }
    
    // Lower teeth (17-32)
    for (let i = 17; i <= 32; i++) {
        teethData[i.toString()] = {
            condition: 'Normal',
            treatment: '',
            notes: '',
            lastUpdated: ''
        };
    }
    
    dentalChart.value.teeth = teethData;
};

// Tooth conditions
const toothConditions = [
    'Normal',
    'Cavity',
    'Filled',
    'Crown',
    'Root Canal',
    'Extracted',
    'Missing',
    'Chipped',
    'Sensitive',
    'Decay',
    'Gum Disease',
    'Impacted'
];

// Treatment options
const treatmentOptions = [
    'None',
    'Cleaning',
    'Filling',
    'Crown',
    'Root Canal',
    'Extraction',
    'Whitening',
    'Braces',
    'Surgery',
    'Other'
];

const handlePatientChange = () => {
    if (selectedPatient.value) {
        const patient = patients.value.find(p => p.id === selectedPatient.value);
        if (patient) {
            dentalChart.value.patientId = patient.id;
            dentalChart.value.patientName = patient.name;
            dentalChart.value.chartDate = selectedDate.value;
            initializeTeeth();
            loadPatientChart(); // In real app, this would load from API
        }
    }
};

const loadPatientChart = () => {
    // In real app, this would load existing chart data from API
    // For demo purposes, we'll set some sample data
    if (dentalChart.value.patientId === 'P001') {
        dentalChart.value.teeth['3'] = {
            condition: 'Cavity',
            treatment: 'Filling',
            notes: 'Small cavity on mesial surface',
            lastUpdated: '2024-01-15'
        };
        dentalChart.value.teeth['14'] = {
            condition: 'Filled',
            treatment: 'Filling',
            notes: 'Composite filling completed',
            lastUpdated: '2024-01-10'
        };
        dentalChart.value.teeth['19'] = {
            condition: 'Root Canal',
            treatment: 'Root Canal',
            notes: 'Root canal therapy completed, needs crown',
            lastUpdated: '2023-11-15'
        };
    }
};

const updateTooth = (toothNumber: string, field: string, value: string) => {
    if (dentalChart.value.teeth[toothNumber]) {
        dentalChart.value.teeth[toothNumber][field] = value;
        dentalChart.value.teeth[toothNumber].lastUpdated = new Date().toISOString().split('T')[0];
    }
};

const saveChart = () => {
    // In real app, this would save to API
    console.log('Saving dental chart:', dentalChart.value);
    alert('Dental chart saved successfully!');
};

const getToothColor = (condition: string) => {
    switch (condition) {
        case 'Normal': return 'bg-white border-gray-300';
        case 'Cavity': return 'bg-red-100 border-red-400';
        case 'Filled': return 'bg-blue-100 border-blue-400';
        case 'Crown': return 'bg-yellow-100 border-yellow-400';
        case 'Root Canal': return 'bg-purple-100 border-purple-400';
        case 'Extracted': return 'bg-gray-100 border-gray-400';
        case 'Missing': return 'bg-gray-100 border-gray-400';
        case 'Chipped': return 'bg-orange-100 border-orange-400';
        case 'Sensitive': return 'bg-pink-100 border-pink-400';
        case 'Decay': return 'bg-red-200 border-red-500';
        case 'Gum Disease': return 'bg-red-300 border-red-600';
        case 'Impacted': return 'bg-indigo-100 border-indigo-400';
        default: return 'bg-white border-gray-300';
    }
};

const getToothTextColor = (condition: string) => {
    switch (condition) {
        case 'Normal': return 'text-gray-700';
        case 'Cavity': return 'text-red-800';
        case 'Filled': return 'text-blue-800';
        case 'Crown': return 'text-yellow-800';
        case 'Root Canal': return 'text-purple-800';
        case 'Extracted': return 'text-gray-800';
        case 'Missing': return 'text-gray-800';
        case 'Chipped': return 'text-orange-800';
        case 'Sensitive': return 'text-pink-800';
        case 'Decay': return 'text-red-900';
        case 'Gum Disease': return 'text-red-900';
        case 'Impacted': return 'text-indigo-800';
        default: return 'text-gray-700';
    }
};

const formatDate = (dateString: string) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
</script>

<template>
    <Head title="Dental Chart" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">Dental Chart</h1>
                <button
                    @click="saveChart"
                    class="bg-darkGreen-900 text-white px-4 py-2 rounded-lg hover:bg-darkGreen-800 transition-colors duration-200"
                >
                    Save Chart
                </button>
            </div>

            <!-- Patient Selection and Chart Info -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Patient</label>
                        <select
                            v-model="selectedPatient"
                            @change="handlePatientChange"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                        >
                            <option value="">Choose a patient...</option>
                            <option v-for="patient in patients" :key="patient.id" :value="patient.id">
                                {{ patient.name }} ({{ patient.age }}, {{ patient.gender }})
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Patient Name</label>
                        <input
                            type="text"
                            :value="dentalChart.patientName"
                            readonly
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-gray-50"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Chart Date</label>
                        <input
                            v-model="selectedDate"
                            type="date"
                            @change="handlePatientChange"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                        />
                    </div>
                </div>

                <!-- Legend -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Chart Legend</h3>
                    <div class="flex flex-wrap gap-2">
                        <div
                            v-for="condition in toothConditions"
                            :key="condition"
                            class="flex items-center gap-2 px-3 py-1 rounded border"
                            :class="getToothColor(condition)"
                        >
                            <div class="w-3 h-3 rounded-full" :class="getToothColor(condition).replace('bg-', 'bg-').replace(' border-', '')"></div>
                            <span class="text-sm font-medium" :class="getToothTextColor(condition)">{{ condition }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dental Chart -->
            <div v-if="dentalChart.patientId" class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6 text-center">Dental Chart - {{ dentalChart.patientName }}</h2>
                
                <!-- Upper Teeth (1-16) -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 text-center">Upper Teeth (Maxillary)</h3>
                    <div class="grid grid-cols-16 gap-1 justify-center">
                        <div
                            v-for="i in 16"
                            :key="i"
                            class="w-12 h-16 border-2 rounded-lg flex flex-col items-center justify-center text-xs font-medium cursor-pointer hover:shadow-md transition-shadow duration-200"
                            :class="getToothColor(dentalChart.teeth[i]?.condition || 'Normal')"
                            @click="() => updateTooth(i.toString(), 'condition', dentalChart.teeth[i]?.condition || 'Normal')"
                        >
                            <span class="text-xs font-bold">{{ i }}</span>
                            <span class="text-xs" :class="getToothTextColor(dentalChart.teeth[i]?.condition || 'Normal')">
                                {{ dentalChart.teeth[i]?.condition || 'Normal' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Lower Teeth (17-32) -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 text-center">Lower Teeth (Mandibular)</h3>
                    <div class="grid grid-cols-16 gap-1 justify-center">
                        <div
                            v-for="i in 16"
                            :key="i + 16"
                            class="w-12 h-16 border-2 rounded-lg flex flex-col items-center justify-center text-xs font-medium cursor-pointer hover:shadow-md transition-shadow duration-200"
                            :class="getToothColor(dentalChart.teeth[i + 16]?.condition || 'Normal')"
                            @click="() => updateTooth((i + 16).toString(), 'condition', dentalChart.teeth[i + 16]?.condition || 'Normal')"
                        >
                            <span class="text-xs font-bold">{{ i + 16 }}</span>
                            <span class="text-xs" :class="getToothTextColor(dentalChart.teeth[i + 16]?.condition || 'Normal')">
                                {{ dentalChart.teeth[i + 16]?.condition || 'Normal' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Tooth Details -->
                <div class="mt-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Tooth Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div
                            v-for="(tooth, toothNumber) in dentalChart.teeth"
                            :key="toothNumber"
                            class="border border-gray-200 rounded-lg p-4"
                            :class="getToothColor(tooth.condition)"
                        >
                            <h4 class="font-semibold text-lg mb-2">Tooth #{{ toothNumber }}</h4>
                            
                            <div class="space-y-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Condition</label>
                                    <select
                                        v-model="tooth.condition"
                                        @change="updateTooth(toothNumber, 'condition', tooth.condition)"
                                        class="w-full border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                                    >
                                        <option v-for="condition in toothConditions" :key="condition" :value="condition">
                                            {{ condition }}
                                        </option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Treatment</label>
                                    <select
                                        v-model="tooth.treatment"
                                        @change="updateTooth(toothNumber, 'treatment', tooth.treatment)"
                                        class="w-full border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                                    >
                                        <option v-for="treatment in treatmentOptions" :key="treatment" :value="treatment">
                                            {{ treatment }}
                                        </option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                    <textarea
                                        v-model="tooth.notes"
                                        @input="updateTooth(toothNumber, 'notes', tooth.notes)"
                                        rows="2"
                                        class="w-full border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-darkGreen-900"
                                        placeholder="Add notes..."
                                    ></textarea>
                                </div>
                                
                                <div class="text-xs text-gray-500">
                                    Last updated: {{ formatDate(tooth.lastUpdated) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No Patient Selected Message -->
            <div v-else class="bg-white rounded-lg shadow-md p-6 text-center">
                <div class="text-gray-500 text-lg">Please select a patient to view their dental chart.</div>
                <div class="text-gray-400 text-sm mt-2">Choose a patient from the dropdown above to begin.</div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.grid-cols-16 {
    grid-template-columns: repeat(16, minmax(0, 1fr));
}
</style> 