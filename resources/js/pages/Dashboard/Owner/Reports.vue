<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import * as XLSX from 'xlsx';
import { FileText, Download, Eye, Calendar, TrendingUp, Users, FileSpreadsheet, X } from 'lucide-vue-next';

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

// Current date (04:53 PM PST, Friday, September 26, 2025)
const today = new Date('2025-09-26T16:53:00-07:00');
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
const formatDateToWords = (dateStr: string, periodType: string): string => {
    const date = new Date(dateStr);
    const monthNames = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];
    const month = monthNames[date.getMonth()];
    const day = date.getDate();
    const year = date.getFullYear();
    if (periodType === 'monthly') {
        return `${month} ${year}`;
    }
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

// Get period key for breakdown (month: YYYY-MM, exact date: YYYY-MM-DD)
const getPeriodKey = (dateStr: string, periodType: string): string => {
    const date = new Date(dateStr);
    if (periodType === 'monthly') {
        return `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}`;
    }
    return dateStr; // Use exact date for daily breakdown
};

// Generate all months in the date range
const getAllMonthsInRange = (start: Date, end: Date): string[] => {
    const months: string[] = [];
    let current = new Date(start.getFullYear(), start.getMonth(), 1);
    const endDate = new Date(end.getFullYear(), end.getMonth(), 1);

    while (current <= endDate) {
        months.push(`${current.getFullYear()}-${(current.getMonth() + 1).toString().padStart(2, '0')}`);
        current.setMonth(current.getMonth() + 1);
    }
    return months;
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

    const start = new Date(startDate.value);
    const end = new Date(endDate.value);
    const rangeDays = (end.getTime() - start.getTime()) / (1000 * 3600 * 24);
    const periodType = rangeDays > 90 ? 'monthly' : 'daily';

    const breakdownMap = new Map<string, { 
        newPatients: number; 
        returningPatients: number; 
        totalPatients: number; 
        completed: number; 
        cancelled: number; 
        totalAppointments: number; 
    }>();

    // Initialize all periods with zeros
    let periods: string[] = [];
    if (periodType === 'monthly') {
        periods = getAllMonthsInRange(start, end);
        periods.forEach(period => {
            breakdownMap.set(period, { 
                newPatients: 0, 
                returningPatients: 0, 
                totalPatients: 0, 
                completed: 0, 
                cancelled: 0, 
                totalAppointments: 0 
            });
        });
    }

    if (selectedReportType.value === 'Patient Report') {
        patientsData.value.forEach(patient => {
            const patientDate = convertDateFormat(patient.date);
            if (!patientDate || !isDateInRange(patientDate, startDate.value, endDate.value)) return;

            const key = getPeriodKey(patientDate, periodType);

            if (!breakdownMap.has(key) && periodType !== 'monthly') {
                breakdownMap.set(key, { 
                    newPatients: 0, 
                    returningPatients: 0, 
                    totalPatients: 0, 
                    completed: 0, 
                    cancelled: 0, 
                    totalAppointments: 0 
                });
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

            if (!breakdownMap.has(key) && periodType !== 'monthly') {
                breakdownMap.set(key, { 
                    newPatients: 0, 
                    returningPatients: 0, 
                    totalPatients: 0, 
                    completed: 0, 
                    cancelled: 0, 
                    totalAppointments: 0 
                });
            }

            const entry = breakdownMap.get(key)!;
            entry.totalAppointments++;
            entry.completed += day.completed || 0;
            entry.cancelled += day.cancelled || 0;
        });
    }

    // Sort keys chronologically
    const sortedKeys = Array.from(breakdownMap.keys()).sort();
    return sortedKeys.map(key => ({ 
        period: formatDateToWords(key, periodType), 
        ...breakdownMap.get(key)! 
    }));
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
    
    // Title
    doc.setFontSize(16);
    doc.setFont('helvetica', 'bold');
    doc.text(selectedReportType.value, 14, 20);
    
    // Date Range
    doc.setFontSize(10);
    doc.setFont('helvetica', 'normal');
    doc.setTextColor(100);
    doc.text(`${formatDateToWords(startDate.value, 'daily')} to ${formatDateToWords(endDate.value, 'daily')}`, 14, 27);
    doc.setTextColor(0);
    
    // Summary Table
    doc.setFontSize(12);
    doc.setFont('helvetica', 'bold');
    doc.text('Summary', 14, 38);
    
    autoTable(doc, {
        head: [['Category', 'Total']],
        body: reportData.value.map(row => [row.category, row.total]),
        startY: 42,
        headStyles: { 
            fillColor: [30, 79, 79], 
            textColor: [255, 255, 255], 
            fontStyle: 'bold',
            fontSize: 11,
            halign: 'left'
        },
        bodyStyles: { 
            fontSize: 10,
            textColor: [50, 50, 50]
        },
        columnStyles: {
            0: { cellWidth: 120 },
            1: { halign: 'right', fontStyle: 'bold' }
        },
        alternateRowStyles: { fillColor: [245, 245, 245] },
        margin: { left: 14, right: 14 },
        theme: 'grid'
    });

    // Breakdown Table
    if (reportBreakdown.value.length > 0) {
        const finalY = ((doc as any).lastAutoTable?.finalY) ?? 42;
        
        doc.setFontSize(12);
        doc.setFont('helvetica', 'bold');
        doc.text('Breakdown', 14, finalY + 12);
        
        const head = selectedReportType.value === 'Patient Report'
            ? [['Period', 'New Patients', 'Returning Patients', 'Total Patients']]
            : [['Period', 'Completed', 'Cancelled', 'Total']];
        const body = reportBreakdown.value.map(row => 
            selectedReportType.value === 'Patient Report'
                ? [row.period, row.newPatients, row.returningPatients, row.totalPatients]
                : [row.period, row.completed, row.cancelled, row.totalAppointments]
        );
        
        autoTable(doc, {
            head,
            body,
            startY: finalY + 16,
            headStyles: { 
                fillColor: [30, 79, 79], 
                textColor: [255, 255, 255], 
                fontStyle: 'bold',
                fontSize: 10,
                halign: 'center'
            },
            bodyStyles: { 
                fontSize: 9,
                textColor: [50, 50, 50]
            },
            columnStyles: {
                0: { cellWidth: 'auto', halign: 'left', fontStyle: 'bold' },
                1: { halign: 'right' },
                2: { halign: 'right' },
                3: { halign: 'right', fontStyle: 'bold' }
            },
            alternateRowStyles: { fillColor: [245, 245, 245] },
            margin: { left: 14, right: 14 },
            theme: 'grid'
        });
    }

    // Footer
    // Use getNumberOfPages if available (older/newer jspdf builds), otherwise fallback to pages length
    const pageCount = typeof (doc as any).getNumberOfPages === 'function'
        ? (doc as any).getNumberOfPages()
        : (doc.internal.pages ? doc.internal.pages.length : 1);

    for (let i = 1; i <= pageCount; i++) {
        doc.setPage(i);
        doc.setFontSize(8);
        doc.setTextColor(150);
        doc.text(
            `Page ${i} of ${pageCount}`,
            doc.internal.pageSize.width / 2,
            doc.internal.pageSize.height - 10,
            { align: 'center' }
        );
        doc.text(
            `Generated on ${new Date().toLocaleDateString()}`,
            14,
            doc.internal.pageSize.height - 10
        );
    }

    doc.save(`${selectedReportType.value.toLowerCase().replace(/ /g, '_')}_${startDate.value}_to_${endDate.value}.pdf`);
};

const handleDownloadExcel = () => {
    if (!hasValidDates.value) {
        alert('Please select both start and end dates before downloading the report.');
        return;
    }
    const wb = XLSX.utils.book_new();

    // Summary Sheet
    const summaryWs = XLSX.utils.json_to_sheet(reportData.value);
    XLSX.utils.book_append_sheet(wb, summaryWs, 'Summary');

    // Breakdown Sheet
    if (reportBreakdown.value.length > 0) {
        const breakdownData = reportBreakdown.value.map(row => 
            selectedReportType.value === 'Patient Report'
                ? {
                      Period: row.period,
                      'New Patients': row.newPatients,
                      'Returning Patients': row.returningPatients,
                      'Total Patients': row.totalPatients
                  }
                : {
                      Period: row.period,
                      Completed: row.completed,
                      Cancelled: row.cancelled,
                      Total: row.totalAppointments
                  }
        );
        const breakdownWs = XLSX.utils.json_to_sheet(breakdownData);
        XLSX.utils.book_append_sheet(wb, breakdownWs, 'Breakdown');
    }

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
        <div class="min-h-screen bg-gray-50 dark:bg-neutral-900 transition-colors duration-300 rounded-xl mt-2 p-4">
            <div class="px-4 py-4 mx-auto">
                <!-- Header Section -->
                <div class="mb-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-4xl font-bold mb-2 text-gray-900 dark:text-white">
                            Reports
                        </h1>
                        <p class="text-lg text-gray-600 dark:text-neutral-400">
                            Generate and export detailed reports for your clinic
                        </p>
                    </div>
                </div>

                <!-- Configuration Card -->
                <div class="bg-white dark:bg-neutral-800 rounded-2xl border border-gray-100 dark:border-neutral-700 shadow-lg mb-6">
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                            <TrendingUp :size="24" class="text-darkGreen-900" />
                            Report Configuration
                        </h2>
                        
                        <form class="space-y-6">
                            <!-- Report Type and Period -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold mb-2 text-gray-700 dark:text-neutral-300">
                                        Report Type <span class="text-red-500">*</span>
                                    </label>
                                    <select 
                                        class="w-full px-4 py-3 rounded-xl border bg-white dark:bg-neutral-900 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white focus:border-darkGreen-900 dark:focus:border-darkGreen-400 focus:outline-none focus:ring-2 focus:ring-darkGreen-900/30 dark:focus:ring-darkGreen-400/30 transition-all duration-200" 
                                        v-model="selectedReportType"
                                    >
                                        <option value="Patient Report">Patient Report</option>
                                        <option value="Appointment Report">Appointment Report</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-2 text-gray-700 dark:text-neutral-300">
                                        Period <span class="text-red-500">*</span>
                                    </label>
                                    <select 
                                        class="w-full px-4 py-3 rounded-xl border bg-white dark:bg-neutral-900 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white focus:border-darkGreen-900 dark:focus:border-darkGreen-400 focus:outline-none focus:ring-2 focus:ring-darkGreen-900/30 dark:focus:ring-darkGreen-400/30 transition-all duration-200"
                                        v-model="selectedPeriod"
                                    >
                                        <option value="Year">Year</option>
                                        <option value="Month">Month</option>
                                        <option value="Weekly">Weekly</option>
                                        <option value="Custom Range">Custom Range</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Year Period -->
                            <div v-if="selectedPeriod === 'Year'" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold mb-2 text-gray-700 dark:text-neutral-300">Start Year</label>
                                        <select
                                            v-model="startYearSelect"
                                            class="w-full px-4 py-3 rounded-xl border bg-white dark:bg-neutral-900 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white focus:border-darkGreen-900 dark:focus:border-darkGreen-400 focus:outline-none focus:ring-2 focus:ring-darkGreen-900/30 dark:focus:ring-darkGreen-400/30 transition-all duration-200"
                                        >
                                            <option v-for="year in years" :key="year.value" :value="year.value">
                                                {{ year.label }}
                                            </option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold mb-2 text-gray-700 dark:text-neutral-300">End Year</label>
                                        <select
                                            v-model="endYearSelect"
                                            class="w-full px-4 py-3 rounded-xl border bg-white dark:bg-neutral-900 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white focus:border-darkGreen-900 dark:focus:border-darkGreen-400 focus:outline-none focus:ring-2 focus:ring-darkGreen-900/30 dark:focus:ring-darkGreen-400/30 transition-all duration-200"
                                        >
                                            <option v-for="year in years" :key="year.value" :value="year.value">
                                                {{ year.label }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Month Period -->
                            <div v-if="selectedPeriod === 'Month'" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <label class="block text-sm font-semibold text-gray-700 dark:text-neutral-300">Start Date</label>
                                        <div class="grid grid-cols-2 gap-3">
                                            <select
                                                v-model="startMonthSelect"
                                                class="px-3 py-3 rounded-xl border bg-white dark:bg-neutral-900 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white focus:border-darkGreen-900 dark:focus:border-darkGreen-400 focus:outline-none focus:ring-2 focus:ring-darkGreen-900/30 dark:focus:ring-darkGreen-400/30 transition-all duration-200"
                                            >
                                                <option v-for="month in months" :key="month.value" :value="month.value">
                                                    {{ month.label }}
                                                </option>
                                            </select>
                                            <select
                                                v-model="startYearSelect"
                                                class="px-3 py-3 rounded-xl border bg-white dark:bg-neutral-900 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white focus:border-darkGreen-900 dark:focus:border-darkGreen-400 focus:outline-none focus:ring-2 focus:ring-darkGreen-900/30 dark:focus:ring-darkGreen-400/30 transition-all duration-200"
                                            >
                                                <option v-for="year in years" :key="year.value" :value="year.value">
                                                    {{ year.label }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block text-sm font-semibold text-gray-700 dark:text-neutral-300">End Date</label>
                                        <div class="grid grid-cols-2 gap-3">
                                            <select
                                                v-model="endMonthSelect"
                                                class="px-3 py-3 rounded-xl border bg-white dark:bg-neutral-900 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white focus:border-darkGreen-900 dark:focus:border-darkGreen-400 focus:outline-none focus:ring-2 focus:ring-darkGreen-900/30 dark:focus:ring-darkGreen-400/30 transition-all duration-200"
                                            >
                                                <option v-for="month in months" :key="month.value" :value="month.value">
                                                    {{ month.label }}
                                                </option>
                                            </select>
                                            <select
                                                v-model="endYearSelect"
                                                class="px-3 py-3 rounded-xl border bg-white dark:bg-neutral-900 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white focus:border-darkGreen-900 dark:focus:border-darkGreen-400 focus:outline-none focus:ring-2 focus:ring-darkGreen-900/30 dark:focus:ring-darkGreen-400/30 transition-all duration-200"
                                            >
                                                <option v-for="year in years" :key="year.value" :value="year.value">
                                                    {{ year.label }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Weekly/Custom Range -->
                            <div v-if="selectedPeriod === 'Weekly' || selectedPeriod === 'Custom Range'" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold mb-2 text-gray-700 dark:text-neutral-300">Start Date</label>
                                        <div class="grid grid-cols-3 gap-2">
                                            <select
                                                v-model="startMonth"
                                                class="px-2 py-3 rounded-xl border bg-white dark:bg-neutral-900 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white focus:border-darkGreen-900 dark:focus:border-darkGreen-400 focus:outline-none focus:ring-2 focus:ring-darkGreen-900/30 dark:focus:ring-darkGreen-400/30 transition-all duration-200 text-sm"
                                            >
                                                <option value="" disabled>Month</option>
                                                <option v-for="month in months" :key="month.value" :value="month.value">
                                                    {{ month.label }}
                                                </option>
                                            </select>
                                            <select
                                                v-model="startDay"
                                                class="px-2 py-3 rounded-xl border bg-white dark:bg-neutral-900 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white focus:border-darkGreen-900 dark:focus:border-darkGreen-400 focus:outline-none focus:ring-2 focus:ring-darkGreen-900/30 dark:focus:ring-darkGreen-400/30 transition-all duration-200 text-sm"
                                            >
                                                <option value="" disabled>Day</option>
                                                <option v-for="day in validDays" :key="day.value" :value="day.value">
                                                    {{ day.label }}
                                                </option>
                                            </select>
                                            <select
                                                v-model="startYear"
                                                class="px-2 py-3 rounded-xl border bg-white dark:bg-neutral-900 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white focus:border-darkGreen-900 dark:focus:border-darkGreen-400 focus:outline-none focus:ring-2 focus:ring-darkGreen-900/30 dark:focus:ring-darkGreen-400/30 transition-all duration-200 text-sm"
                                            >
                                                <option value="" disabled>Year</option>
                                                <option v-for="year in years" :key="year.value" :value="year.value">
                                                    {{ year.label }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold mb-2 text-gray-700 dark:text-neutral-300">End Date</label>
                                        <div class="grid grid-cols-3 gap-2">
                                            <select
                                                v-model="endMonth"
                                                class="px-2 py-3 rounded-xl border bg-white dark:bg-neutral-900 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white focus:border-darkGreen-900 dark:focus:border-darkGreen-400 focus:outline-none focus:ring-2 focus:ring-darkGreen-900/30 dark:focus:ring-darkGreen-400/30 transition-all duration-200 text-sm"
                                            >
                                                <option value="" disabled>Month</option>
                                                <option v-for="month in months" :key="month.value" :value="month.value">
                                                    {{ month.label }}
                                                </option>
                                            </select>
                                            <select
                                                v-model="endDay"
                                                class="px-2 py-3 rounded-xl border bg-white dark:bg-neutral-900 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white focus:border-darkGreen-900 dark:focus:border-darkGreen-400 focus:outline-none focus:ring-2 focus:ring-darkGreen-900/30 dark:focus:ring-darkGreen-400/30 transition-all duration-200 text-sm"
                                            >
                                                <option value="" disabled>Day</option>
                                                <option v-for="day in validDays" :key="day.value" :value="day.value">
                                                    {{ day.label }}
                                                </option>
                                            </select>
                                            <select
                                                v-model="endYear"
                                                class="px-2 py-3 rounded-xl border bg-white dark:bg-neutral-900 border-gray-300 dark:border-neutral-700 text-gray-900 dark:text-white focus:border-darkGreen-900 dark:focus:border-darkGreen-400 focus:outline-none focus:ring-2 focus:ring-darkGreen-900/30 dark:focus:ring-darkGreen-400/30 transition-all duration-200 text-sm"
                                            >
                                                <option value="" disabled>Year</option>
                                                <option v-for="year in years" :key="year.value" :value="year.value">
                                                    {{ year.label }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <button 
                                        type="button" 
                                        class="px-4 py-2.5 bg-gray-100 dark:bg-neutral-700 text-gray-700 dark:text-neutral-300 rounded-xl hover:bg-gray-200 dark:hover:bg-neutral-600 transition-all duration-200 text-sm font-medium flex items-center gap-2"
                                        @click="setStartToday"
                                    >
                                        <Calendar :size="16" />
                                        Set Start to Today
                                    </button>
                                    <button 
                                        type="button" 
                                        class="px-4 py-2.5 bg-gray-100 dark:bg-neutral-700 text-gray-700 dark:text-neutral-300 rounded-xl hover:bg-gray-200 dark:hover:bg-neutral-600 transition-all duration-200 text-sm font-medium flex items-center gap-2"
                                        @click="setEndToday"
                                    >
                                        <Calendar :size="16" />
                                        Set End to Today
                                    </button>
                                </div>
                            </div>

                            <!-- Error Message -->
                            <div v-if="errorMessage" class="p-3 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-300 text-sm">
                                {{ errorMessage }}
                            </div>

                            <!-- Generate Button -->
                            <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-neutral-700">
                                <button 
                                    type="button" 
                                    class="px-6 py-3 bg-darkGreen-900 text-white rounded-xl hover:bg-darkGreen-800 transition-all duration-200 font-semibold disabled:bg-gray-400 disabled:cursor-not-allowed flex items-center gap-2 shadow-lg"
                                    :disabled="!startMonth || !startDay || !startYear || !endMonth || !endDay || !endYear || isLoading"
                                    @click="handleGenerateReport"
                                >
                                    <TrendingUp :size="20" />
                                    {{ isLoading ? 'Generating...' : 'Generate Report' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Report Display -->
                <div v-if="showReport" class="space-y-6">
                    <!-- Report Header -->
                    <div class="bg-white dark:bg-neutral-800 rounded-2xl border border-gray-100 dark:border-neutral-700 shadow-lg overflow-hidden">
                        <div class="bg-darkGreen-900 p-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-white/10 rounded-lg">
                                        <Users v-if="selectedReportType === 'Patient Report'" class="text-white" :size="24" />
                                        <Calendar v-else class="text-white" :size="24" />
                                    </div>
                                    <div>
                                        <h3 class="text-2xl font-bold text-white">{{ selectedReportType }}</h3>
                                        <p class="text-white/80 text-sm mt-1">
                                            {{ formatDateToWords(startDate, 'daily') }} to {{ formatDateToWords(endDate, 'daily') }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button 
                                        @click="handlePreview" 
                                        class="p-2.5 bg-white/10 hover:bg-white/20 text-white rounded-lg transition-all duration-200"
                                        title="Preview"
                                    >
                                        <Eye :size="20" />
                                    </button>
                                    <button 
                                        @click="handleDownloadPDF" 
                                        class="p-2.5 bg-white/10 hover:bg-white/20 text-white rounded-lg transition-all duration-200"
                                        title="Download PDF"
                                    >
                                        <Download :size="20" />
                                    </button>
                                    <button 
                                        @click="handleDownloadExcel" 
                                        class="p-2.5 bg-white/10 hover:bg-white/20 text-white rounded-lg transition-all duration-200"
                                        title="Download Excel"
                                    >
                                        <FileSpreadsheet :size="20" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- No Data State -->
                        <div v-if="!hasReportData" class="p-12 text-center">
                            <div class="w-16 h-16 bg-gray-100 dark:bg-neutral-700 rounded-full flex items-center justify-center mx-auto mb-4">
                                <FileText class="text-gray-400 dark:text-neutral-500" :size="32" />
                            </div>
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No Data Available</h4>
                            <p class="text-sm text-gray-600 dark:text-neutral-400">No data found for the selected date range.</p>
                        </div>

                        <!-- Report Content -->
                        <div v-else class="p-6">
                            <!-- Summary Section -->
                            <div class="mb-8">
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Summary</h4>
                                <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-neutral-700">
                                    <table class="w-full">
                                        <thead>
                                            <tr class="bg-darkGreen-900">
                                                <th class="text-left px-6 py-4 text-white font-semibold">Category</th>
                                                <th class="text-right px-6 py-4 text-white font-semibold">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                                            <tr v-for="(row, index) in reportData" :key="row.category" 
                                                :class="index % 2 === 0 ? 'bg-white dark:bg-neutral-800' : 'bg-gray-50 dark:bg-neutral-800/50'">
                                                <td class="px-6 py-4 text-gray-900 dark:text-white">{{ row.category }}</td>
                                                <td class="px-6 py-4 text-right font-semibold text-darkGreen-900 dark:text-darkGreen-400">{{ row.total }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Breakdown Section -->
                            <div v-if="reportBreakdown.length > 0">
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Breakdown</h4>
                                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-neutral-700">
                                    <table class="w-full">
                                        <thead>
                                            <tr class="bg-darkGreen-900">
                                                <th class="text-left px-6 py-4 text-white font-semibold whitespace-nowrap">Period</th>
                                                <th v-if="selectedReportType === 'Patient Report'" class="text-right px-6 py-4 text-white font-semibold whitespace-nowrap">New Patients</th>
                                                <th v-if="selectedReportType === 'Patient Report'" class="text-right px-6 py-4 text-white font-semibold whitespace-nowrap">Returning Patients</th>
                                                <th v-if="selectedReportType === 'Patient Report'" class="text-right px-6 py-4 text-white font-semibold whitespace-nowrap">Total Patients</th>
                                                <th v-if="selectedReportType === 'Appointment Report'" class="text-right px-6 py-4 text-white font-semibold whitespace-nowrap">Completed</th>
                                                <th v-if="selectedReportType === 'Appointment Report'" class="text-right px-6 py-4 text-white font-semibold whitespace-nowrap">Cancelled</th>
                                                <th v-if="selectedReportType === 'Appointment Report'" class="text-right px-6 py-4 text-white font-semibold whitespace-nowrap">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                                            <tr v-for="(row, index) in reportBreakdown" :key="row.period"
                                                :class="index % 2 === 0 ? 'bg-white dark:bg-neutral-800' : 'bg-gray-50 dark:bg-neutral-800/50'">
                                                <td class="px-6 py-4 text-gray-900 dark:text-white font-medium">{{ row.period }}</td>
                                                <td v-if="selectedReportType === 'Patient Report'" class="px-6 py-4 text-right text-gray-700 dark:text-neutral-300">{{ row.newPatients }}</td>
                                                <td v-if="selectedReportType === 'Patient Report'" class="px-6 py-4 text-right text-gray-700 dark:text-neutral-300">{{ row.returningPatients }}</td>
                                                <td v-if="selectedReportType === 'Patient Report'" class="px-6 py-4 text-right font-semibold text-darkGreen-900 dark:text-darkGreen-400">{{ row.totalPatients }}</td>
                                                <td v-if="selectedReportType === 'Appointment Report'" class="px-6 py-4 text-right text-gray-700 dark:text-neutral-300">{{ row.completed }}</td>
                                                <td v-if="selectedReportType === 'Appointment Report'" class="px-6 py-4 text-right text-gray-700 dark:text-neutral-300">{{ row.cancelled }}</td>
                                                <td v-if="selectedReportType === 'Appointment Report'" class="px-6 py-4 text-right font-semibold text-darkGreen-900 dark:text-darkGreen-400">{{ row.totalAppointments }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-wrap gap-3 mt-6 pt-6 border-t border-gray-200 dark:border-neutral-700">
                                <button 
                                    @click="handlePreview" 
                                    class="flex items-center gap-2 px-5 py-2.5 bg-gray-100 dark:bg-neutral-700 text-gray-700 dark:text-neutral-300 rounded-xl hover:bg-gray-200 dark:hover:bg-neutral-600 transition-all duration-200 font-medium"
                                >
                                    <Eye :size="18" />
                                    Preview
                                </button>
                                <button 
                                    @click="handleDownloadPDF" 
                                    class="flex items-center gap-2 px-5 py-2.5 bg-darkGreen-900 text-white rounded-xl hover:bg-darkGreen-800 transition-all duration-200 font-medium"
                                >
                                    <Download :size="18" />
                                    Download PDF
                                </button>
                                <button 
                                    @click="handleDownloadExcel" 
                                    class="flex items-center gap-2 px-5 py-2.5 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-all duration-200 font-medium"
                                >
                                    <FileSpreadsheet :size="18" />
                                    Download Excel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preview Modal -->
                <div v-if="showPreview" class="fixed inset-0 flex items-center justify-center bg-black/60 backdrop-blur-sm z-50 p-4">
                    <div class="bg-white dark:bg-neutral-800 rounded-2xl shadow-2xl w-full max-w-5xl max-h-[90vh] overflow-hidden border border-gray-200 dark:border-neutral-700 flex flex-col">
                        <!-- Modal Header -->
                        <div class="bg-darkGreen-900 p-6 flex-shrink-0">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-2xl font-bold text-white">{{ selectedReportType }}</h3>
                                    <p class="text-white/80 text-sm mt-1">{{ formatDateToWords(startDate, 'daily') }} to {{ formatDateToWords(endDate, 'daily') }}</p>
                                </div>
                                <button 
                                    @click="showPreview = false" 
                                    class="p-2 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200"
                                >
                                    <X :size="24" />
                                </button>
                            </div>
                        </div>

                        <!-- Modal Body -->
                        <div class="flex-1 overflow-y-auto p-6">
                            <!-- No Data State -->
                            <div v-if="!hasReportData" class="text-center py-16">
                                <div class="w-16 h-16 bg-gray-100 dark:bg-neutral-700 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <FileText class="text-gray-400 dark:text-neutral-500" :size="32" />
                                </div>
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No Data Available</h4>
                                <p class="text-sm text-gray-600 dark:text-neutral-400">No data found for the selected date range.</p>
                            </div>

                            <!-- Report Content -->
                            <div v-else class="space-y-8">
                                <!-- Summary -->
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Summary</h4>
                                    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-neutral-700">
                                        <table class="w-full text-sm">
                                            <thead>
                                                <tr class="bg-darkGreen-900">
                                                    <th class="text-left px-6 py-3 text-white font-semibold">Category</th>
                                                    <th class="text-right px-6 py-3 text-white font-semibold">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                                                <tr v-for="(row, index) in reportData" :key="row.category" 
                                                    :class="index % 2 === 0 ? 'bg-white dark:bg-neutral-800' : 'bg-gray-50 dark:bg-neutral-800/50'">
                                                    <td class="px-6 py-3 text-gray-900 dark:text-white">{{ row.category }}</td>
                                                    <td class="px-6 py-3 text-right font-semibold text-darkGreen-900 dark:text-darkGreen-400">{{ row.total }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Breakdown -->
                                <div v-if="reportBreakdown.length > 0">
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Breakdown</h4>
                                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-neutral-700">
                                        <table class="w-full text-sm">
                                            <thead>
                                                <tr class="bg-darkGreen-900">
                                                    <th class="text-left px-6 py-3 text-white font-semibold whitespace-nowrap">Period</th>
                                                    <th v-if="selectedReportType === 'Patient Report'" class="text-right px-6 py-3 text-white font-semibold whitespace-nowrap">New Patients</th>
                                                    <th v-if="selectedReportType === 'Patient Report'" class="text-right px-6 py-3 text-white font-semibold whitespace-nowrap">Returning Patients</th>
                                                    <th v-if="selectedReportType === 'Patient Report'" class="text-right px-6 py-3 text-white font-semibold whitespace-nowrap">Total Patients</th>
                                                    <th v-if="selectedReportType === 'Appointment Report'" class="text-right px-6 py-3 text-white font-semibold whitespace-nowrap">Completed</th>
                                                    <th v-if="selectedReportType === 'Appointment Report'" class="text-right px-6 py-3 text-white font-semibold whitespace-nowrap">Cancelled</th>
                                                    <th v-if="selectedReportType === 'Appointment Report'" class="text-right px-6 py-3 text-white font-semibold whitespace-nowrap">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                                                <tr v-for="(row, index) in reportBreakdown" :key="row.period"
                                                    :class="index % 2 === 0 ? 'bg-white dark:bg-neutral-800' : 'bg-gray-50 dark:bg-neutral-800/50'">
                                                    <td class="px-6 py-3 text-gray-900 dark:text-white font-medium">{{ row.period }}</td>
                                                    <td v-if="selectedReportType === 'Patient Report'" class="px-6 py-3 text-right text-gray-700 dark:text-neutral-300">{{ row.newPatients }}</td>
                                                    <td v-if="selectedReportType === 'Patient Report'" class="px-6 py-3 text-right text-gray-700 dark:text-neutral-300">{{ row.returningPatients }}</td>
                                                    <td v-if="selectedReportType === 'Patient Report'" class="px-6 py-3 text-right font-semibold text-darkGreen-900 dark:text-darkGreen-400">{{ row.totalPatients }}</td>
                                                    <td v-if="selectedReportType === 'Appointment Report'" class="px-6 py-3 text-right text-gray-700 dark:text-neutral-300">{{ row.completed }}</td>
                                                    <td v-if="selectedReportType === 'Appointment Report'" class="px-6 py-3 text-right text-gray-700 dark:text-neutral-300">{{ row.cancelled }}</td>
                                                    <td v-if="selectedReportType === 'Appointment Report'" class="px-6 py-3 text-right font-semibold text-darkGreen-900 dark:text-darkGreen-400">{{ row.totalAppointments }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.bg-darkGreen-900 {
    background-color: #1e4f4f;
}

.text-darkGreen-900 {
    color: #1e4f4f;
}

.bg-darkGreen-800 {
    background-color: #2d5f5c;
}

.dark .text-darkGreen-400 {
    color: #4a9d9d;
}

.bg-neutral-750 {
    background-color: #2a2a2a;
}

/* Smooth transitions */
button,
input,
select {
    transition: all 0.2s ease-in-out;
}

/* Custom scrollbar */
.overflow-y-auto::-webkit-scrollbar,
.overflow-x-auto::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.overflow-y-auto::-webkit-scrollbar-track,
.overflow-x-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb,
.overflow-x-auto::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 4px;
}

.dark .overflow-y-auto::-webkit-scrollbar-thumb,
.dark .overflow-x-auto::-webkit-scrollbar-thumb {
    background: #4b5563;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover,
.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}

.dark .overflow-y-auto::-webkit-scrollbar-thumb:hover,
.dark .overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: #6b7280;
}
</style>