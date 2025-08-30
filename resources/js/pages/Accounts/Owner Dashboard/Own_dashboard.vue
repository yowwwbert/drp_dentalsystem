<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

interface DashboardData {
    appointmentStats: {
        pending: number;
        completed: number;
        cancelled: number;
        total: number;
        overview: Array<{
            date: string;
            completed: number;
            cancelled: number;
            total: number;
        }>;
    };
    scheduledAppointments: Array<{
        patientName: string;
        date: string;
        startTime: string;
        branch: string;
    }>;
    branchStats: {
        totalBranches: number;
        branches: Array<{
            id: string;
            name: string;
            appointmentCount: number;
            dentistCount: number;
            staffCount: number;
            status: string;
        }>;
    };
    financialOverview: {
        totalRevenue: number;
        monthlyRevenue: number;
        pendingPayments: number;
        paymentMethods: string[];
    };
    recentActivities: Array<{
        type: string;
        description: string;
        branch: string;
        timestamp: string;
    }>;
}

const dashboardData = ref<DashboardData | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);

const fetchDashboardData = async () => {
    try {
        loading.value = true;
        const response = await axios.get('/dashboard/owner/api/dashboard-data');
        dashboardData.value = response.data;
    } catch (err) {
        error.value = 'Failed to fetch dashboard data';
        console.error('Error fetching dashboard data:', err);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchDashboardData();
});
</script>

<template>
    <Head title="Owner Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">Owner Dashboard</h1>
                <button 
                    @click="fetchDashboardData" 
                    :disabled="loading"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                >
                    {{ loading ? 'Refreshing...' : 'Refresh' }}
                </button>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="flex justify-center items-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
                <p class="text-red-800">{{ error }}</p>
                <button 
                    @click="fetchDashboardData" 
                    class="mt-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
                >
                    Try Again
                </button>
            </div>

            <!-- Dashboard Content -->
            <div v-else-if="dashboardData" class="space-y-6">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Appointments</p>
                                <p class="text-2xl font-bold text-gray-900">{{ dashboardData.appointmentStats.total }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 rounded-lg">
                                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Pending</p>
                                <p class="text-2xl font-bold text-gray-900">{{ dashboardData.appointmentStats.pending }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Completed</p>
                                <p class="text-2xl font-bold text-gray-900">{{ dashboardData.appointmentStats.completed }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-red-100 rounded-lg">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Cancelled</p>
                                <p class="text-2xl font-bold text-gray-900">{{ dashboardData.appointmentStats.cancelled }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Branch Statistics -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Branch Overview</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div v-for="branch in dashboardData.branchStats.branches" :key="branch.id" class="border rounded-lg p-4">
                            <h3 class="font-medium text-gray-900">{{ branch.name }}</h3>
                            <div class="mt-2 space-y-1 text-sm text-gray-600">
                                <p>Appointments: {{ branch.appointmentCount }}</p>
                                <p>Dentists: {{ branch.dentistCount }}</p>
                                <p>Staff: {{ branch.staffCount }}</p>
                                <p class="text-green-600 font-medium">{{ branch.status }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Weekly Overview Chart -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Weekly Appointment Overview</h2>
                    <div class="flex items-end space-x-2 h-32">
                        <div v-for="(day, index) in dashboardData.appointmentStats.overview" :key="index" class="flex-1 flex flex-col items-center">
                            <div class="w-full bg-gray-200 rounded-t" :style="{ height: `${Math.max(day.total * 10, 20)}px` }">
                                <div class="bg-blue-600 rounded-t" :style="{ height: `${Math.max(day.completed * 10, 0)}px` }"></div>
                            </div>
                            <p class="text-xs text-gray-600 mt-1">{{ day.date }}</p>
                            <p class="text-xs font-medium text-gray-900">{{ day.total }}</p>
                        </div>
                    </div>
                </div>

                <!-- Scheduled Appointments -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Upcoming Appointments</h2>
                    <div v-if="dashboardData.scheduledAppointments.length > 0" class="space-y-3">
                        <div v-for="appointment in dashboardData.scheduledAppointments" :key="appointment.patientName" class="flex items-center justify-between p-3 border rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900">{{ appointment.patientName }}</p>
                                <p class="text-sm text-gray-600">{{ appointment.date }} at {{ appointment.startTime }}</p>
                                <p class="text-sm text-gray-500">{{ appointment.branch }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 text-gray-500">
                        <p>No upcoming appointments</p>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Recent Activities</h2>
                    <div v-if="dashboardData.recentActivities.length > 0" class="space-y-3">
                        <div v-for="activity in dashboardData.recentActivities" :key="activity.timestamp" class="flex items-start space-x-3 p-3 border rounded-lg">
                            <div class="p-2 bg-blue-100 rounded-full">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ activity.description }}</p>
                                <p class="text-xs text-gray-500">{{ activity.branch }} • {{ activity.timestamp }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 text-gray-500">
                        <p>No recent activities</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template> 