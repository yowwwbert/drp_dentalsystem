<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
// @ts-ignore
import jsPDF from 'jspdf';
// @ts-ignore
import autoTable from 'jspdf-autotable';
// @ts-ignore
import * as XLSX from 'xlsx';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Reports', href: '/reports' },
];

const showReport = ref(false);
const showPreview = ref(false);
const selectedReportType = ref('Patient Report');
const startDate = ref('');
const endDate = ref('');

// Sample data from dashboardData.json
const dashboardData = {
    appointments: {
        pending: 3,
        completed: 8,
        cancelled: 2,
        overview: [
            { date: "5 Apr", completed: 2, cancelled: 0, total: 2 },
            { date: "6 Apr", completed: 1, cancelled: 1, total: 2 },
            { date: "7 Apr", completed: 3, cancelled: 0, total: 3 },
            { date: "8 Apr", completed: 0, cancelled: 1, total: 1 },
            { date: "9 Apr", completed: 2, cancelled: 0, total: 2 },
            { date: "10 Apr", completed: 0, cancelled: 0, total: 0 },
            { date: "11", completed: 0, cancelled: 0, total: 0 }
        ]
    },
    scheduledAppointments: [
        {
            patientName: "Atencio, Casandra",
            date: "April 01, 2025",
            startTime: "10:00 AM",
            branch: "Alabang"
        },
        {
            patientName: "Arao, Gabriel Jophel",
            date: "March 31, 2025",
            startTime: "10:00 AM",
            branch: "Manila"
        },
        {
            patientName: "Atencio, Robert",
            date: "April 02, 2025",
            startTime: "10:00 AM",
            branch: "Manila"
        }
    ]
};

// Helper function to check if a date is within the selected range
const isDateInRange = (dateString: string, startDate: string, endDate: string): boolean => {
    if (!startDate || !endDate) return false;
    
    // Convert date string to Date object
    const date = new Date(dateString);
    const start = new Date(startDate);
    const end = new Date(endDate);
    
    // Set time to start of day for accurate comparison
    date.setHours(0, 0, 0, 0);
    start.setHours(0, 0, 0, 0);
    end.setHours(0, 0, 0, 0);
    
    return date >= start && date <= end;
};

// Helper function to convert date format for comparison
const convertDateFormat = (dateString: string): string => {
    // Handle different date formats
    if (dateString.includes('Apr')) {
        // Convert "5 Apr" to "2025-04-05" format
        const parts = dateString.split(' ');
        const day = parts[0].padStart(2, '0');
        const month = '04'; // April
        return `2025-${month}-${day}`;
    }
    return dateString;
};

const reportData = computed(() => {
    if (!hasValidDates.value) {
        return [];
    }

    if (selectedReportType.value === 'Patient Report') {
        // Filter patients based on date range
        const filteredPatients = dashboardData.scheduledAppointments.filter(appointment => {
            const appointmentDate = convertDateFormat(appointment.date);
            return isDateInRange(appointmentDate, startDate.value, endDate.value);
        });

        const totalPatients = filteredPatients.length;
        const newPatients = Math.floor(totalPatients * 0.7); // Simulate 70% new patients
        const returningPatients = totalPatients - newPatients;

        return [
            { category: 'New Patients', total: newPatients },
            { category: 'Returning Patients', total: returningPatients },
            { category: 'Total Patients', total: totalPatients }
        ];
    } else {
        // Filter appointments based on date range
        const filteredOverview = dashboardData.appointments.overview.filter(day => {
            const dayDate = convertDateFormat(day.date);
            return isDateInRange(dayDate, startDate.value, endDate.value);
        });

        const completed = filteredOverview.reduce((sum, day) => sum + day.completed, 0);
        const cancelled = filteredOverview.reduce((sum, day) => sum + day.cancelled, 0);
        const total = filteredOverview.reduce((sum, day) => sum + day.total, 0);

        return [
            { category: 'Completed Appointments', total: completed },
            { category: 'Cancelled Appointments', total: cancelled },
            { category: 'Total Appointments', total: total }
        ];
    }
});

const hasValidDates = computed(() => {
    return startDate.value && endDate.value;
});

const hasReportData = computed(() => {
    // Check if there's any data in the report and dates are selected
    return hasValidDates.value && reportData.value.some(row => row.total > 0);
});

const handlePreview = () => {
    if (!hasValidDates.value) {
        alert('Please select both start and end dates before previewing the report.');
        return;
    }
    showPreview.value = true;
};

const handleDownloadPDF = () => {
    if (!hasValidDates.value) {
        alert('Please select both start and end dates before downloading the report.');
        return;
    }
    const doc = new jsPDF();
    doc.text(`${selectedReportType.value}`, 14, 16);
    autoTable(doc, {
        head: [['Category', 'Total']],
        body: reportData.value.map(row => [row.category, row.total]),
        startY: 22,
    });
    doc.save(`${selectedReportType.value.toLowerCase().replace(' ', '_')}.pdf`);
};

const handleDownloadExcel = () => {
    if (!hasValidDates.value) {
        alert('Please select both start and end dates before downloading the report.');
        return;
    }
    const ws = XLSX.utils.json_to_sheet(reportData.value);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Report');
    XLSX.writeFile(wb, `${selectedReportType.value.toLowerCase().replace(' ', '_')}.xlsx`);
};

const handleGenerateReport = () => {
    if (!hasValidDates.value) {
        alert('Please select both start and end dates before generating the report.');
        return;
    }
    showReport.value = true;
};
</script>

<template>
    <Head title="Reports" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 rounded-xl p-4 bg-gray-50 min-h-screen relative">
            <div class="flex justify-between items-center mb-4">
                <h1 class="text-3xl font-bold text-gray-900">Reports</h1>
            </div>
                <form class="space-y-3">
                    <div class="flex gap-4 mb-2">
                        <div class="flex-1">
                            <label class="font-medium mr-2 text-gray-700">Report Type</label>
                            <select class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-900" v-model="selectedReportType">
                                <option>Patient Report</option>
                                <option>Appointment Report</option>
                            </select>
                        </div>
                        <div class="flex-1">
                            <label class="font-medium mr-2 text-gray-700">Period</label>
                            <select class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-900">
                                <option>Week</option>
                                <option>Month</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex gap-4 mb-2">
                        <div class="flex-1">
                            <label class="font-medium mr-2 text-gray-700">Start Date</label>
                            <input type="date" class="w-full border border-gray-300 rounded px-4 py-2 w-64 focus:outline-none focus:ring-2 focus:ring-teal-900" placeholder="dd/mm/yyyy" v-model="startDate" />
                        </div>
                        <div class="flex-1">
                            <label class="font-medium mr-2 text-gray-700">End Date</label>
                            <input type="date" class="w-full border border-gray-300 rounded px-4 py-2 w-64 focus:outline-none focus:ring-2 focus:ring-teal-900" placeholder="dd/mm/yyyy" v-model="endDate" />
                        </div>
                    </div>
                    <button type="button" class="bg-teal-900 text-white px-4 py-2 rounded mt-4 hover:bg-teal-700 transition" @click="handleGenerateReport">Generate Report</button>
                </form>
                <div v-if="showPreview" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-40 z-50">
                    <div class="bg-white rounded-lg shadow-lg p-6 min-w-[400px] relative">
                        <h3 class="font-bold mb-4 text-lg">{{ selectedReportType }}</h3>
                        <div v-if="!hasReportData" class="text-center py-8 text-gray-700">
                            <p class="text-lg">No reports available</p>
                            <p class="text-sm mt-2">No data found for the selected date range.</p>
                        </div>
                        <table v-else class="w-full border-collapse text-sm">
                            <thead>
                                <tr class="bg-blue-600 text-white">
                                    <th class="text-left px-4 py-3 font-semibold">Category</th>
                                    <th class="text-left px-4 py-3 font-semibold">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, index) in reportData" :key="row.category" 
                                    :class="index % 2 === 0 ? 'bg-gray-50' : 'bg-white'">
                                    <td class="px-4 py-3 border-b">{{ row.category }}</td>
                                    <td class="px-4 py-3 border-b font-medium">{{ row.total }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <button @click="showPreview = false" class="absolute top-4 right-4 text-gray-500 hover:text-black text-xl font-bold">&times;</button>
                    </div>
                </div>
                <div v-if="showReport" class="mb-6">
                    <h3 class="font-bold mb-2">{{ selectedReportType }} Report</h3>
                    <div v-if="!hasReportData" class="text-center py-8 text-gray-500 border rounded">
                        <p class="text-lg">No reports available</p>
                        <p class="text-sm mt-2">No data found for the selected date range.</p>
                    </div>
                    <div v-else>
                        <table class="w-full border text-sm">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="text-left px-2 py-1">Category</th>
                                    <th class="text-left px-2 py-1">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in reportData" :key="row.category">
                                    <td class="px-2 py-1">{{ row.category }}</td>
                                    <td class="px-2 py-1">{{ row.total }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="flex gap-2 mt-4">
                            <button @click="handlePreview" class="bg-teal-900 text-white px-4 py-2 rounded">Preview</button>
                            <button @click="handleDownloadPDF" class="bg-blue-600 text-white px-4 py-2 rounded">Download as PDF</button>
                            <button @click="handleDownloadExcel" class="bg-green-700 text-white px-4 py-2 rounded">Download as Excel</button>
                        </div>
                    </div>
                </div>
        </div>
    </AppLayout>
</template> 