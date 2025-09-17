<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage, Link } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import { Bell, Plus, ChevronDown } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';
import type { Auth } from '@/types';

// Interfaces
interface AppointmentOverview {
    date: string;
    completed: number;
    cancelled: number;
    scheduled: number;
}

interface ScheduledAppointment {
    patient_name: string;
    schedule_date: string;
    start_time: string;
    branch_name: string;
}

interface DashboardData {
    appointments: {
        scheduled: number;
        completed: number;
        cancelled: number;
        overview: AppointmentOverview[];
    };
    scheduledAppointments: ScheduledAppointment[];
}

const page = usePage<{ auth: Auth }>();
const user = computed(() => page.props.auth.user);
const userType = computed(() => user.value?.user_type || 'User');

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
];


// Dashboard data structure - will be populated from API
interface AppointmentOverview {
    date: string;
    completed: number;
    cancelled: number;
    total: number;
}

interface ScheduledAppointment {
    patientName: string;
    date: string;
    startTime: string;
    branch: string;
}

interface DashboardData {
    appointments: {
        pending: number;
        completed: number;
        cancelled: number;
        overview: AppointmentOverview[];
    };
    scheduledAppointments: ScheduledAppointment[];
}

const dashboardData = ref<DashboardData>({
    appointments: {
        pending: 0,
        completed: 0,
        cancelled: 0,
        overview: []
    },
    scheduledAppointments: []
});

// Chart data for analytics
const chartData = computed(() => dashboardData.value.appointments.overview);

// Max chart value for scaling
const maxChartValue = computed(() => {
    const max = Math.max(
        ...chartData.value.flatMap(data => [data.completed || 0, data.cancelled || 0, data.scheduled || 0]),
        1
    );
    // Round up to the next multiple of 10 for dynamic scaling
    return Math.ceil(max / 10) * 10;
});

// Sorting
const sortField = ref<keyof ScheduledAppointment>('patient_name');
const sortDirection = ref<'asc' | 'desc'>('asc');

const sortAppointments = (field: keyof ScheduledAppointment) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
};

const sortedAppointments = computed(() => {
    const appointments = [...dashboardData.value.scheduledAppointments];

    return appointments.sort((a, b) => {
        let aValue: any = a[sortField.value];
        let bValue: any = b[sortField.value];

        if (sortField.value === 'schedule_date') {
            aValue = new Date(aValue as string).getTime();
            bValue = new Date(bValue as string).getTime();
        }
        if (sortField.value === 'start_time') {
            aValue = new Date(`2000-01-01 ${aValue}`).getTime();
            bValue = new Date(`2000-01-01 ${bValue}`).getTime();
        }

        return sortDirection.value === 'asc'
            ? aValue > bValue ? 1 : -1
            : aValue < bValue ? -1 : 1;
    });
});

// User details
const userFirstName = computed(() => user.value?.first_name || 'User');
const userPosition = computed(() => user.value?.user_type || 'User');

// Permissions
const canAddPatients = computed(() => ['Owner', 'Staff'].includes(userType.value));
const canAddAppointments = computed(() => ['Owner', 'Staff', 'Patient'].includes(userType.value));

const appointmentListRoute = computed(() => {
    const basePath = `/dashboard/${userType.value.toLowerCase()}/appointments/AppointmentList`;
    return userType.value === 'Patient' ? '/dashboard' : basePath;
});
</script>

<template>

    <Head title="Dashboard"/>

    <AppLayout>
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
                <p class="text-gray-600">Welcome, {{ userFirstName }}</p>
            </div>
            <div class="flex items-center gap-4">
                <Link v-if="canAddPatients" href="/patients/create"
                    class="bg-green-800 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors flex items-center gap-2">
                <Plus :size="16" /> Add Patient
                </Link>
                <Link v-if="canAddAppointments" href="/appointment"
                    class="bg-green-800 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors flex items-center gap-2">
                <Plus :size="16" /> Add Appointment
                </Link>
                <Link href="/Appointment" class="p-2 text-gray-600 hover:text-gray-800">
                    <Bell :size="20" />
                </Link>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-green-800 rounded-full flex items-center justify-center">
                        <span class="text-white text-sm font-bold">{{ userFirstName.charAt(0).toUpperCase() }}</span>
                    </div>
                    <div>
                        <p class="text-sm font-medium">{{ userFirstName }}</p>
                        <p class="text-xs text-gray-500">({{ userPosition }})</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Scheduled</h3>
                <div class="text-3xl font-bold text-gray-900 mb-4">{{ dashboardData.appointments.scheduled }}</div>
                <Link :href="appointmentListRoute"
                    class="w-full bg-green-800 text-white py-2 rounded-md hover:bg-green-700 transition-colors flex items-center justify-center">
                See more</Link>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Completed</h3>
                <div class="text-3xl font-bold text-gray-900 mb-4">{{ dashboardData.appointments.completed }}</div>
                <Link :href="appointmentListRoute"
                    class="w-full bg-green-800 text-white py-2 rounded-md hover:bg-green-700 transition-colors flex items-center justify-center">
                See more</Link>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Cancelled</h3>
                <div class="text-3xl font-bold text-gray-900 mb-4">{{ dashboardData.appointments.cancelled }}</div>
                <Link :href="appointmentListRoute"
                    class="w-full bg-green-800 text-white py-2 rounded-md hover:bg-green-700 transition-colors flex items-center justify-center">
                See more</Link>
            </div>
        </div>

        <!-- Analytics & Table -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Chart -->
            <div class="bg-white rounded-lg shadow-md p-6 pb-12">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Analytics</h3>
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-md font-medium text-gray-700">Appointments Overview</h4>
                    <!-- Legend -->
                    <div class="flex gap-4 text-sm">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-[#1E4F4F]"></div><span>Completed</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-[#3E7F7B]"></div><span>Cancelled</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-[#9ebebc]"></div><span>Scheduled</span>
                        </div>
                    </div>
                </div>
                <!-- Chart -->
                <div v-if="chartData.length > 0" class="relative h-64 flex">
                    <!-- Y-axis -->
                    <div class="w-6 flex flex-col justify-between text-sm text-gray-500 relative">
                        <span>{{ Math.ceil(maxChartValue) }}</span>
                        <span>{{ Math.ceil(maxChartValue * 0.75) }}</span>
                        <span>{{ Math.ceil(maxChartValue * 0.5) }}</span>
                        <span>{{ Math.ceil(maxChartValue * 0.25) }}</span>
                        <span>0</span>
                        <div class="absolute top-0 bottom-0 right-0 border-r border-[#3E7F7B]"></div>
                    </div>

                    <!-- Chart area -->
                    <div class="flex-1 relative border-b border-[#3E7F7B]">
                        <!-- Horizontal grid lines -->
                        <div class="absolute inset-0 flex flex-col justify-between z-0">
                            <div class="w-full border-t border-[#3E7F7B]/25"></div>
                            <div class="w-full border-t border-[#3E7F7B]/25"></div>
                            <div class="w-full border-t border-[#3E7F7B]/25"></div>
                            <div class="w-full border-t border-[#3E7F7B]/25"></div>
                            <div class="w-full"></div>
                        </div>

                        <!-- Bars -->
                        <div class="h-full flex items-end justify-between px-2 relative">
                            <div v-for="(data, index) in chartData" :key="index"
                                class="flex flex-col items-center gap-1 h-full relative flex-1">

                                <div class="flex gap-1.5 items-end h-full z-10">
                                    <!-- Completed -->
                                    <div class="w-4 bg-[#1E4F4F] relative group"
                                        :style="{ height: `${Math.max((data.completed / maxChartValue) * 100, 1)}%` }">
                                        <div class="tooltip hidden group-hover:block">Completed: {{ data.completed }}
                                        </div>
                                    </div>
                                    <!-- Cancelled -->
                                    <div class="w-4 bg-[#3E7F7B] relative group"
                                        :style="{ height: `${Math.max((data.cancelled / maxChartValue) * 100, 1)}%` }">
                                        <div class="tooltip hidden group-hover:block">Cancelled: {{ data.cancelled }}
                                        </div>
                                    </div>
                                    <!-- Scheduled -->
                                    <div class="w-4 bg-[#9ebebc] relative group"
                                        :style="{ height: `${Math.max((data.scheduled / maxChartValue) * 100, 1)}%` }">
                                        <div class="tooltip hidden group-hover:block">Scheduled: {{ data.scheduled }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dates under X-axis -->
                        <div class="flex justify-between px-2 mt-2">
                            <span v-for="(data, index) in chartData" :key="index" class="text-sm text-gray-600">
                                {{ data.date }}
                            </span>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center text-gray-500">No appointment data available for the chart.</div>


            </div>

            <!-- Table -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Scheduled Appointments</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-green-800 text-white">
                                <th @click="sortAppointments('patient_name')"
                                    class="px-4 py-3 text-left text-sm font-medium cursor-pointer hover:bg-green-700">
                                    Patient Name</th>
                                <th @click="sortAppointments('schedule_date')"
                                    class="px-4 py-3 text-left text-sm font-medium cursor-pointer hover:bg-green-700">
                                    Date</th>
                                <th @click="sortAppointments('start_time')"
                                    class="px-4 py-3 text-left text-sm font-medium cursor-pointer hover:bg-green-700">
                                    Start Time</th>
                                <th @click="sortAppointments('branch_name')"
                                    class="px-4 py-3 text-left text-sm font-medium cursor-pointer hover:bg-green-700">
                                    Branch</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="appointment in sortedAppointments" :key="appointment.patient_name"
                                class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-900">{{ appointment.patient_name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ appointment.schedule_date }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ appointment.start_time }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ appointment.branch_name }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.bg-green-800 {
    background-color: #1e4f4f;
}

.bg-green-700 {
    background-color: #2d6a6a;
}

.hover\:bg-green-700:hover {
    background-color: #2d6a6a;
}

.tooltip {
    position: absolute;
    top: -2rem;
    left: 50%;
    transform: translateX(-50%);
    background-color: #333;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
    z-index: 10;
}
</style>
