<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import * as XLSX from 'xlsx';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Reports', href: '/reports' },
];

const showReport = ref(false);
const showPreview = ref(false);
const selectedReportType = ref('Patient Report');
const selectedPeriod = ref('Custom Range');
const isLoading = ref(false);
const errorMessage = ref('');

// Date selection refs for custom and breakdowns
const startMonth = ref('');
const startDay = ref('');
const startYear = ref('');
const endMonth = ref('');
const endDay = ref('');
const endYear = ref('');

// Current date (03:41 AM PST, Thursday, September 25, 2025)
const today = new Date('2025-09-25T03:41:00-07:00');
const currentYear = today.getFullYear().toString();
const currentMonth = (today.getMonth() + 1).toString().padStart(2, '0');
const currentDay = today.getDate().toString().padStart(2, '0');

// Additional selectors for Year and Month periods
const startYearSelect = ref(currentYear);
const endYearSelect = ref(currentYear);
const startMonthSelect = ref(currentMonth);
const endMonthSelect = ref(currentMonth);

// Generate options for dropdowns
const months = [
    { value: '01', label: 'January' },
    { value: '02', label: 'February' },
    { value: '03', label: 'March' },
    { value: '04', label: 'April' },
    { value: '05', label: 'May' },
    { value: '06', label: 'June' },
    { value: '07', label: 'July' },
    { value: '08', label: 'August' },
    { value: '09', label: 'September' },
    { value: '10', label: 'October' },
    { value: '11', label: 'November' },
    { value: '12', label: 'December' },
];
const days = Array.from({ length: 31 }, (_, i) => ({
    value: (i + 1).toString().padStart(2, '0'),
    label: (i + 1).toString(),
}));
const years = Array.from({ length: 10 }, (_, i) => {
    const year = new Date().getFullYear() - 5 + i;
    return { value: year.toString(), label: year.toString() };
});

// Helper function to convert date to words
const formatDateToWords = (dateStr: string): string => {
    const date = new Date(dateStr);
    const monthNames = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];
    const month = monthNames[date.getMonth()];
    const day = date.getDate();
    const year = date.getFullYear();
    return `${month} ${day}, ${year}`;
};

// Combine month, day, year into YYYY-MM-DD format
const startDate = computed(() => {
    if (startYear.value && startMonth.value && startDay.value) {
        return `${startYear.value}-${startMonth.value}-${startDay.value}`;
    }
    return '';
});

const endDate = computed(() => {
    if (endYear.value && endMonth.value && endDay.value) {
        return `${endYear.value}-${endMonth.value}-${endDay.value}`;
    }
    return '';
});

// Validate days based on month and year
const validDays = computed(() => {
    const month = startMonth.value || endMonth.value;
    const year = startYear.value || endYear.value;
    if (!month || !year) return days;

    const daysInMonth = new Date(parseInt(year), parseInt(month), 0).getDate();
    return days.filter(day => parseInt(day.value) <= daysInMonth);
});

// Reactive state for fetched data
const patientsData = ref<any[]>([]);
const appointmentsData = ref<any[]>([]);

// Helper function to check if a date is within the selected range
const isDateInRange = (dateString: string, startDate: string, endDate: string): boolean => {
    if (!startDate || !endDate || !dateString) return false;

    const date = new Date(dateString);
    const start = new Date(startDate);
    const end = new Date(endDate);

    date.setHours(0, 0, 0, 0);
    start.setHours(0, 0, 0, 0);
    end.setHours(0, 0, 0, 0);

    return date >= start && date <= end;
};

// Helper function to convert date format for comparison
const convertDateFormat = (dateString?: string): string => {
    if (!dateString || typeof dateString !== 'string') return '';

    // Handle YYYY-MM-DD HH:MM:SS format (e.g., "2025-08-21 17:14:23")
    if (dateString.includes(' ')) {
        return dateString.split(' ')[0]; // Extract YYYY-MM-DD
    }

    // Handle ISO format (e.g., "2025-08-28T00:00:00.000000Z")
    if (dateString.includes('T')) {
        return dateString.split('T')[0]; // Extract YYYY-MM-DD
    }

    // Handle "X Apr" format (e.g., "5 Apr")
    if (dateString.includes('Apr')) {
        const parts = dateString.split(' ');
        if (parts.length < 2) return '';
        const day = parts[0].padStart(2, '0');
        const month = '04';
        return `2025-${month}-${day}`;
    }

    // Assume already in YYYY-MM-DD format
    return dateString;
};

// Get period key for breakdown (month: YYYY-MM, week: YYYY-WW, day: YYYY-MM-DD)
const getPeriodKey = (dateStr: string, periodType: string): string => {
    const date = new Date(dateStr);
    if (periodType === 'monthly') {
        return `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}`;
    } else if (periodType === 'weekly') {
        const year = date.getFullYear();
        const month = date.getMonth();
        const day = date.getDate();
        const weekStart = new Date(year, month, day - date.getDay() + 1); // Monday start
        return `${weekStart.getFullYear()}-${(weekStart.getMonth() + 1).toString().padStart(2, '0')}-${weekStart.getDate().toString().padStart(2, '0')}`;
    } else if (periodType === 'daily') {
        return dateStr;
    }
    return '';
};

// Set date range based on selected period
const setDateRange = () => {
    if (selectedPeriod.value === 'Year') {
        startYear.value = startYearSelect.value;
        startMonth.value = '01';
        startDay.value = '01';
        endYear.value = endYearSelect.value;
        endMonth.value = '12';
        endDay.value = '31';
    } else if (selectedPeriod.value === 'Month') {
        const lastDayOfStartMonth = new Date(parseInt(startYearSelect.value), parseInt(startMonthSelect.value), 0).getDate().toString().padStart(2, '0');
        const lastDayOfEndMonth = new Date(parseInt(endYearSelect.value), parseInt(endMonthSelect.value), 0).getDate().toString().padStart(2, '0');
        startYear.value = startYearSelect.value;
        startMonth.value = startMonthSelect.value;
        startDay.value = '01';
        endYear.value = endYearSelect.value;
        endMonth.value = endMonthSelect.value;
        endDay.value = lastDayOfEndMonth;
    } else if (selectedPeriod.value === 'Weekly') {
        // Use start date dropdowns for weekly start
        const start = new Date(`${startYear.value}-${startMonth.value}-${startDay.value}`);
        const end = new Date(start);
        end.setDate(start.getDate() + 6); // 7-day period
        endYear.value = end.getFullYear().toString();
        endMonth.value = (end.getMonth() + 1).toString().padStart(2, '0');
        endDay.value = end.getDate().toString().padStart(2, '0');
    } else if (selectedPeriod.value === 'Custom Range') {
        // User-defined, no auto-set
    }
};

// Watch for changes to update range
watch([selectedPeriod, startYearSelect, endYearSelect, startMonthSelect, endMonthSelect, startYear, startMonth, startDay], () => {
    setDateRange();
});

// Set "Today" date range
const setStartToday = () => {
    startYear.value = currentYear;
    startMonth.value = currentMonth;
    startDay.value = currentDay;
    startYearSelect.value = currentYear;
    startMonthSelect.value = currentMonth;
    selectedPeriod.value = 'Custom Range'; // Switch to custom
};

const setEndToday = () => {
    endYear.value = currentYear;
    endMonth.value = currentMonth;
    endDay.value = currentDay;
    endYearSelect.value = currentYear;
    endMonthSelect.value = currentMonth;
    selectedPeriod.value = 'Custom Range'; // Switch to custom
};

// Fetch data from API
const fetchReportData = async () => {
    if (!hasValidDates.value) return;

    isLoading.value = true;
    errorMessage.value = '';

    try {
        if (selectedReportType.value === 'Patient Report') {
            const response = await axios.get('/dashboard/patients', {
                params: {
                    start_date: startDate.value,
                    end_date: endDate.value,
                },
            });
            patientsData.value = Array.isArray(response.data)
                ? response.data.map(item => ({
                      ...item,
                      date: item.created_at, // Use created_at as date
                  }))
                : [];
            console.log('Patients Data:', patientsData.value); // Debug
            // Fetch appointments for returning patient check
            const appointmentsResponse = await axios.get('/dashboard/appointments', {
                params: {
                    start_date: '1970-01-01', // Fetch all for history
                    end_date: endDate.value,
                },
            });
            appointmentsData.value = Array.isArray(appointmentsResponse.data.appointments)
                ? appointmentsResponse.data.appointments
                : [];
            console.log('Appointments Data:', appointmentsData.value); // Debug
        } else {
            const response = await axios.get('/dashboard/appointments', {
                params: {
                    start_date: startDate.value,
                    end_date: endDate.value,
                },
            });
            appointmentsData.value = Array.isArray(response.data.appointments)
                ? response.data.appointments.map((item: any) => ({
                      date: item.date,
                      completed: item.status === 'Completed' ? 1 : 0,
                      cancelled: item.status === 'Cancelled' ? 1 : 0,
                      total: 1,
                  }))
                : [];
            console.log('Appointments Data:', appointmentsData.value); // Debug
        }
    } catch (error) {
        errorMessage.value = 'Failed to fetch report data. Please try again.';
        console.error('Error fetching data:', error);
    } finally {
        isLoading.value = false;
    }
};

const reportData = computed(() => {
    if (!hasValidDates.value) {
        return [];
    }

    if (selectedReportType.value === 'Patient Report') {
        const filteredPatients = patientsData.value.filter(patient => {
            if (!patient?.date) return false;
            const patientDate = convertDateFormat(patient.date);
            return patientDate && isDateInRange(patientDate, startDate.value, endDate.value);
        });

        const newPatients = filteredPatients.filter(patient => {
            const hasPriorAppointments = appointmentsData.value.some(appointment => {
                if (appointment.patient_id !== patient.patient_id) return false;
                const appointmentDate = convertDateFormat(appointment.date);
                return appointmentDate && new Date(appointmentDate) < new Date(startDate.value);
            });
            return !hasPriorAppointments;
        }).length;

        const totalPatients = filteredPatients.length;
        const returningPatients = totalPatients - newPatients;

        return [
            { category: 'New Patients', total: newPatients },
            { category: 'Returning Patients', total: returningPatients },
            { category: 'Total Patients', total: totalPatients }
        ];
    } else {
        const filteredOverview = appointmentsData.value.filter(day => {
            if (!day?.date) return false;
            const dayDate = convertDateFormat(day.date);
            return dayDate && isDateInRange(dayDate, startDate.value, endDate.value);
        });

        const completed = filteredOverview.reduce((sum: number, day: any) => sum + (day.completed || 0), 0);
        const cancelled = filteredOverview.reduce((sum: number, day: any) => sum + (day.cancelled || 0), 0);
        const total = filteredOverview.reduce((sum: number, day: any) => sum + (day.total || 0), 0);

        return [
            { category: 'Completed Appointments', total: completed },
            { category: 'Cancelled Appointments', total: cancelled },
            { category: 'Total Appointments', total: total }
        ];
    }
});

// Breakdown computed
const reportBreakdown = computed(() => {
    if (!hasReportData.value) return [];

    let periodType = '';
    const start = new Date(startDate.value);
    const end = new Date(endDate.value);
    const rangeDays = (end.getTime() - start.getTime()) / (1000 * 3600 * 24);

    if (selectedPeriod.value === 'Year' || rangeDays > 90) {
        periodType = 'monthly';
    } else if (selectedPeriod.value === 'Month' || rangeDays > 7) {
        periodType = 'weekly';
    } else {
        periodType = 'daily';
    }

    const breakdownMap = new Map<string, { newPatients: number; returningPatients: number; totalPatients: number; completed: number; cancelled: number; totalAppointments: number; }>();

    if (selectedReportType.value === 'Patient Report') {
        patientsData.value.forEach(patient => {
            const patientDate = convertDateFormat(patient.date);
            if (!patientDate || !isDateInRange(patientDate, startDate.value, endDate.value)) return;

            const key = getPeriodKey(patientDate, periodType);

            if (!breakdownMap.has(key)) {
                breakdownMap.set(key, { newPatients: 0, returningPatients: 0, totalPatients: 0, completed: 0, cancelled: 0, totalAppointments: 0 });
            }

            const entry = breakdownMap.get(key)!;
            entry.totalPatients++;
            const hasPriorAppointments = appointmentsData.value.some(appointment => {
                if (appointment.patient_id !== patient.patient_id) return false;
                const appointmentDate = convertDateFormat(appointment.date);
                return appointmentDate && new Date(appointmentDate) < new Date(startDate.value);
            });
            if (hasPriorAppointments) {
                entry.returningPatients++;
            } else {
                entry.newPatients++;
            }
        });
    } else {
        appointmentsData.value.forEach(day => {
            const dayDate = convertDateFormat(day.date);
            if (!dayDate || !isDateInRange(dayDate, startDate.value, endDate.value)) return;

            const key = getPeriodKey(dayDate, periodType);

            if (!breakdownMap.has(key)) {
                breakdownMap.set(key, { newPatients: 0, returningPatients: 0, totalPatients: 0, completed: 0, cancelled: 0, totalAppointments: 0 });
            }

            const entry = breakdownMap.get(key)!;
            entry.totalAppointments++;
            entry.completed += day.completed || 0;
            entry.cancelled += day.cancelled || 0;
        });
    }

    // Sort keys chronologically
    const sortedKeys = Array.from(breakdownMap.keys()).sort();
    return sortedKeys.map(key => ({ period: formatDateToWords(key), ...breakdownMap.get(key)! }));
});

const hasValidDates = computed(() => {
    return startDate.value && endDate.value;
});

const hasReportData = computed(() => {
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
    doc.text(`${selectedReportType.value} (${formatDateToWords(startDate.value)} to ${formatDateToWords(endDate.value)})`, 14, 16);
    autoTable(doc, {
        head: [['Category', 'Total']],
        body: reportData.value.map(row => [row.category, row.total]),
        startY: 22,
    });
    doc.save(`${selectedReportType.value.toLowerCase().replace(' ', '_')}_${startDate.value}_to_${endDate.value}.pdf`);
};

const handleDownloadExcel = () => {
    if (!hasValidDates.value) {
        alert('Please select both start and end dates before downloading the report.');
        return;
    }
    const ws = XLSX.utils.json_to_sheet(reportData.value);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Report');
    XLSX.writeFile(wb, `${selectedReportType.value.toLowerCase().replace(' ', '_')}_${startDate.value}_to_${endDate.value}.xlsx`);
};

const handleGenerateReport = async () => {
    if (!hasValidDates.value) {
        alert('Please select both start and end dates before generating the report.');
        return;
    }
    await fetchReportData();
    if (!errorMessage.value) {
        showReport.value = true;
    }
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
                        <select 
                            class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-900 bg-white" 
                            v-model="selectedReportType"
                        >
                            <option value="Patient Report">Patient Report</option>
                            <option value="Appointment Report">Appointment Report</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label class="font-medium mr-2 text-gray-700">Period</label>
                        <select 
                            class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-900 bg-white"
                            v-model="selectedPeriod"
                        >
                            <option>Year</option>
                            <option>Month</option>
                            <option>Weekly</option>
                            <option>Custom Range</option>
                        </select>
                    </div>
                </div>
                <div v-if="selectedPeriod === 'Year'" class="flex gap-4 mb-2">
                    <div class="flex-1">
                        <label class="font-medium mr-2 text-gray-700">Start Year</label>
                        <select
                            v-model="startYearSelect"
                            class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-900 bg-white"
                        >
                            <option v-for="year in years" :key="year.value" :value="year.value">
                                {{ year.label }}
                            </option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label class="font-medium mr-2 text-gray-700">End Year</label>
                        <select
                            v-model="endYearSelect"
                            class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-900 bg-white"
                        >
                            <option v-for="year in years" :key="year.value" :value="year.value">
                                {{ year.label }}
                            </option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <button 
                            type="button" 
                            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-500 transition"
                            @click="setStartToday"
                        >
                            Set Start to Today
                        </button>
                    </div>
                    <div class="flex-1">
                        <button 
                            type="button" 
                            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-500 transition"
                            @click="setEndToday"
                        >
                            Set End to Today
                        </button>
                    </div>
                </div>
                <div v-if="selectedPeriod === 'Month'" class="flex gap-4 mb-2">
                    <div class="flex-1">
                        <label class="font-medium mr-2 text-gray-700">Start Month</label>
                        <select
                            v-model="startMonthSelect"
                            class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-900 bg-white"
                        >
                            <option v-for="month in months" :key="month.value" :value="month.value">
                                {{ month.label }}
                            </option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label class="font-medium mr-2 text-gray-700">Start Year</label>
                        <select
                            v-model="startYearSelect"
                            class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-900 bg-white"
                        >
                            <option v-for="year in years" :key="year.value" :value="year.value">
                                {{ year.label }}
                            </option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label class="font-medium mr-2 text-gray-700">End Month</label>
                        <select
                            v-model="endMonthSelect"
                            class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-900 bg-white"
                        >
                            <option v-for="month in months" :key="month.value" :value="month.value">
                                {{ month.label }}
                            </option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label class="font-medium mr-2 text-gray-700">End Year</label>
                        <select
                            v-model="endYearSelect"
                            class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-900 bg-white"
                        >
                            <option v-for="year in years" :key="year.value" :value="year.value">
                                {{ year.label }}
                            </option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <button 
                            type="button" 
                            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-500 transition"
                            @click="setStartToday"
                        >
                            Set Start to Today
                        </button>
                    </div>
                    <div class="flex-1">
                        <button 
                            type="button" 
                            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-500 transition"
                            @click="setEndToday"
                        >
                            Set End to Today
                        </button>
                    </div>
                </div>
                <div class="flex gap-4 mb-2" v-if="selectedPeriod === 'Weekly' || selectedPeriod === 'Custom Range'">
                    <div class="flex-1">
                        <label class="font-medium mr-2 text-gray-700">Start Date</label>
                        <div class="flex gap-2">
                            <select
                                v-model="startMonth"
                                class="w-1/3 border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-900 bg-white"
                            >
                                <option value="" disabled selected>Month</option>
                                <option v-for="month in months" :key="month.value" :value="month.value">
                                    {{ month.label }}
                                </option>
                            </select>
                            <select
                                v-model="startDay"
                                class="w-1/3 border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-900 bg-white"
                            >
                                <option value="" disabled selected>Day</option>
                                <option v-for="day in validDays" :key="day.value" :value="day.value">
                                    {{ day.label }}
                                </option>
                            </select>
                            <select
                                v-model="startYear"
                                class="w-1/3 border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-900 bg-white"
                            >
                                <option value="" disabled selected>Year</option>
                                <option v-for="year in years" :key="year.value" :value="year.value">
                                    {{ year.label }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="flex-1">
                        <label class="font-medium mr-2 text-gray-700">End Date</label>
                        <div class="flex gap-2">
                            <select
                                v-model="endMonth"
                                class="w-1/3 border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-900 bg-white"
                            >
                                <option value="" disabled selected>Month</option>
                                <option v-for="month in months" :key="month.value" :value="month.value">
                                    {{ month.label }}
                                </option>
                            </select>
                            <select
                                v-model="endDay"
                                class="w-1/3 border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-900 bg-white"
                            >
                                <option value="" disabled selected>Day</option>
                                <option v-for="day in validDays" :key="day.value" :value="day.value">
                                    {{ day.label }}
                                </option>
                            </select>
                            <select
                                v-model="endYear"
                                class="w-1/3 border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-900 bg-white"
                            >
                                <option value="" disabled selected>Year</option>
                                <option v-for="year in years" :key="year.value" :value="year.value">
                                    {{ year.label }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
                <div v-if="selectedPeriod === 'Weekly' || selectedPeriod === 'Custom Range'" class="flex gap-4 mb-2">
                    <div class="flex-1">
                        <button 
                            type="button" 
                            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-500 transition"
                            @click="setStartToday"
                        >
                            Set Start to Today
                        </button>
                    </div>
                    <div class="flex-1">
                        <button 
                            type="button" 
                            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-500 transition"
                            @click="setEndToday"
                        >
                            Set End to Today
                        </button>
                    </div>
                </div>
                <div v-if="errorMessage" class="text-red-600 text-sm mt-2">
                    {{ errorMessage }}
                </div>
                <button 
                    type="button" 
                    class="bg-teal-900 text-white px-4 py-2 rounded mt-4 hover:bg-teal-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed"
                    :disabled="!startMonth || !startDay || !startYear || !endMonth || !endDay || !endYear || isLoading"
                    @click="handleGenerateReport"
                >
                    {{ isLoading ? 'Generating...' : 'Generate Report' }}
                </button>
            </form>
            <div v-if="showPreview" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-40 z-50">
                <div class="bg-white rounded-lg shadow-lg p-6 min-w-[400px] relative">
                    <h3 class="font-bold mb-4 text-lg">{{ selectedReportType }} ({{ formatDateToWords(startDate) }} to {{ formatDateToWords(endDate) }})</h3>
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
                    <h4 v-if="reportBreakdown.length > 0" class="font-bold mt-4">Breakdown</h4>
                    <table v-if="reportBreakdown.length > 0" class="w-full border-collapse text-sm mt-2">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="text-left px-2 py-1">Period</th>
                                <th v-if="selectedReportType === 'Patient Report'" class="text-left px-2 py-1">New Patients</th>
                                <th v-if="selectedReportType === 'Patient Report'" class="text-left px-2 py-1">Returning Patients</th>
                                <th v-if="selectedReportType === 'Patient Report'" class="text-left px-2 py-1">Total Patients</th>
                                <th v-if="selectedReportType === 'Appointment Report'" class="text-left px-2 py-1">Completed</th>
                                <th v-if="selectedReportType === 'Appointment Report'" class="text-left px-2 py-1">Cancelled</th>
                                <th v-if="selectedReportType === 'Appointment Report'" class="text-left px-2 py-1">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in reportBreakdown" :key="row.period">
                                <td class="px-2 py-1">{{ row.period }}</td>
                                <td v-if="selectedReportType === 'Patient Report'" class="px-2 py-1">{{ row.newPatients }}</td>
                                <td v-if="selectedReportType === 'Patient Report'" class="px-2 py-1">{{ row.returningPatients }}</td>
                                <td v-if="selectedReportType === 'Patient Report'" class="px-2 py-1">{{ row.totalPatients }}</td>
                                <td v-if="selectedReportType === 'Appointment Report'" class="px-2 py-1">{{ row.completed }}</td>
                                <td v-if="selectedReportType === 'Appointment Report'" class="px-2 py-1">{{ row.cancelled }}</td>
                                <td v-if="selectedReportType === 'Appointment Report'" class="px-2 py-1">{{ row.totalAppointments }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <button 
                        @click="showPreview = false" 
                        class="absolute top-4 right-4 text-gray-500 hover:text-black text-xl font-bold"
                    >
                        &times;
                    </button>
                </div>
            </div>
            <div v-if="showReport" class="mb-6">
                <h3 class="font-bold mb-2">{{ selectedReportType }} Report ({{ formatDateToWords(startDate) }} to {{ formatDateToWords(endDate) }})</h3>
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
                    <h4 v-if="reportBreakdown.length > 0" class="font-bold mt-4">Breakdown</h4>
                    <table v-if="reportBreakdown.length > 0" class="w-full border text-sm mt-2">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="text-left px-2 py-1">Period</th>
                                <th v-if="selectedReportType === 'Patient Report'" class="text-left px-2 py-1">New Patients</th>
                                <th v-if="selectedReportType === 'Patient Report'" class="text-left px-2 py-1">Returning Patients</th>
                                <th v-if="selectedReportType === 'Patient Report'" class="text-left px-2 py-1">Total Patients</th>
                                <th v-if="selectedReportType === 'Appointment Report'" class="text-left px-2 py-1">Completed</th>
                                <th v-if="selectedReportType === 'Appointment Report'" class="text-left px-2 py-1">Cancelled</th>
                                <th v-if="selectedReportType === 'Appointment Report'" class="text-left px-2 py-1">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in reportBreakdown" :key="row.period">
                                <td class="px-2 py-1">{{ row.period }}</td>
                                <td v-if="selectedReportType === 'Patient Report'" class="px-2 py-1">{{ row.newPatients }}</td>
                                <td v-if="selectedReportType === 'Patient Report'" class="px-2 py-1">{{ row.returningPatients }}</td>
                                <td v-if="selectedReportType === 'Patient Report'" class="px-2 py-1">{{ row.totalPatients }}</td>
                                <td v-if="selectedReportType === 'Appointment Report'" class="px-2 py-1">{{ row.completed }}</td>
                                <td v-if="selectedReportType === 'Appointment Report'" class="px-2 py-1">{{ row.cancelled }}</td>
                                <td v-if="selectedReportType === 'Appointment Report'" class="px-2 py-1">{{ row.totalAppointments }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="flex gap-2 mt-4">
                        <button 
                            @click="handlePreview" 
                            class="bg-teal-900 text-white px-4 py-2 rounded hover:bg-teal-700 transition"
                        >
                            Preview
                        </button>
                        <button 
                            @click="handleDownloadPDF" 
                            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-500 transition"
                        >
                            Download as PDF
                        </button>
                        <button 
                            @click="handleDownloadExcel" 
                            class="bg-green-700 text-white px-4 py-2 rounded hover:bg-green-600 transition"
                        >
                            Download as Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>