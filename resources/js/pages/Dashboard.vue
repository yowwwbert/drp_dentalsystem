<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import { Bell, Plus, ChevronDown } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';
import dashboardDataJson from '../tempData/dashboardData.json';
import type { Auth } from '@/types';

const page = usePage<{ auth: Auth }>();
const user = computed(() => page.props.auth.user);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

// Import data from JSON file
const dashboardData = ref(dashboardDataJson);

// Chart data for analytics
const chartData = ref(dashboardData.value.appointments.overview);

// Sorting functionality for appointments table
const sortField = ref('patientName');
const sortDirection = ref('asc');

const sortAppointments = (field: string) => {
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
    let aValue: any = a[sortField.value as keyof typeof a];
    let bValue: any = b[sortField.value as keyof typeof b];
    
    // Handle date sorting
    if (sortField.value === 'date') {
      aValue = new Date(aValue as string).getTime();
      bValue = new Date(bValue as string).getTime();
    }
    
    // Handle time sorting
    if (sortField.value === 'startTime') {
      aValue = new Date(`2000-01-01 ${aValue}`).getTime();
      bValue = new Date(`2000-01-01 ${bValue}`).getTime();
    }
    
    if (sortDirection.value === 'asc') {
      return aValue > bValue ? 1 : -1;
    } else {
      return aValue < bValue ? -1 : 1;
    }
  });
});

// Get user's first name for welcome message
const userFirstName = computed(() => {
    if (user.value?.first_name) {
        return user.value.first_name;
    }
    return 'User';
});

// Get user's position/role
const userPosition = computed(() => {
    return user.value?.user_type || 'User';
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- Header Section -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                <p class="text-gray-600">Welcome, {{ userFirstName }}</p>
            </div>
            <div class="flex items-center gap-4">
                <Link href="#" class="bg-green-800 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors flex items-center gap-2">
                    <Plus :size="16" />
                    Add Patient
                </Link>
                <Link href="#" class="bg-green-800 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors flex items-center gap-2">
                    <Plus :size="16" />
                    Add Appointment
                </Link>
                <Link href="appointment/select-date-and-time" class="p-2 text-gray-600 hover:text-gray-800">
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

        <!-- Appointment Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Pending Appointments -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Pending Appointments</h3>
                <div class="text-3xl font-bold text-gray-900 mb-4">{{ dashboardData.appointments.pending }}</div>
                <Link href="#" class="w-full bg-green-800 text-white py-2 rounded-md hover:bg-green-700 transition-colors flex items-center justify-center">
                    See more
                </Link>
            </div>

            <!-- Completed Appointments -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Completed Appointments</h3>
                <div class="text-3xl font-bold text-gray-900 mb-4">{{ dashboardData.appointments.completed }}</div>
                <Link href="#" class="w-full bg-green-800 text-white py-2 rounded-md hover:bg-green-700 transition-colors flex items-center justify-center">
                    See more
                </Link>
            </div>

            <!-- Cancelled Appointments -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Cancelled Appointments</h3>
                <div class="text-3xl font-bold text-gray-900 mb-4">{{ dashboardData.appointments.cancelled }}</div>
                <Link href="#" class="w-full bg-green-800 text-white py-2 rounded-md hover:bg-green-700 transition-colors flex items-center justify-center">
                    See more
                </Link>
            </div>
        </div>

        <!-- Analytics and Scheduled Appointments Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Analytics Section -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Analytics</h3>
                    <div class="relative">
                        <select class="appearance-none bg-gray-100 border border-gray-300 rounded-md px-3 py-1 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option>Appointment Chart</option>
                        </select>
                        <ChevronDown :size="16" class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-500 pointer-events-none" />
                    </div>
                </div>
                
                <div class="mb-4">
                    <h4 class="text-md font-medium text-gray-700 mb-3">Appointments Overview</h4>
                    
                    <!-- Chart Legend -->
                    <div class="flex gap-4 mb-4 text-sm">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-green-500 rounded"></div>
                            <span>Completed</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-red-500 rounded"></div>
                            <span>Cancelled</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-blue-500 rounded"></div>
                            <span>Total</span>
                        </div>
                    </div>

                    <!-- Simple Bar Chart -->
                    <div class="relative h-48 border-l border-b border-gray-300">
                        <!-- Y-axis labels -->
                        <div class="absolute left-0 top-0 h-full flex flex-col justify-between text-xs text-gray-500">
                            <span>3</span>
                            <span>2</span>
                            <span>1</span>
                            <span>0</span>
                        </div>
                        
                        <!-- Chart bars -->
                        <div class="ml-8 h-full flex items-end justify-between">
                            <div v-for="(data, index) in chartData" :key="index" class="flex flex-col items-center gap-1">
                                <div class="flex gap-1 items-end">
                                    <div class="w-4 bg-green-500 rounded-t transition-all duration-300" :style="{ height: data.completed > 0 ? `${(data.completed / 3) * 100}%` : '2px' }"></div>
                                    <div class="w-4 bg-red-500 rounded-t transition-all duration-300" :style="{ height: data.cancelled > 0 ? `${(data.cancelled / 3) * 100}%` : '2px' }"></div>
                                    <div class="w-4 bg-blue-500 rounded-t transition-all duration-300" :style="{ height: data.total > 0 ? `${(data.total / 3) * 100}%` : '2px' }"></div>
                                </div>
                                <span class="text-xs text-gray-600">{{ data.date }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center text-sm text-gray-500 mt-2">Appointments Count</div>
                </div>
            </div>

            <!-- Scheduled Appointments Section -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Scheduled Appointments</h3>
                    <p class="text-sm text-gray-600">Appointments</p>
                </div>

                <!-- Appointments Table -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-green-800 text-white">
                                <th @click="sortAppointments('patientName')" class="px-4 py-3 text-left text-sm font-medium cursor-pointer hover:bg-green-700 transition-colors">
                                    Patient Name
                                    <ChevronDown :size="12" class="inline ml-1 transition-transform" :class="{ 'rotate-180': sortField === 'patientName' && sortDirection === 'desc' }" />
                                </th>
                                <th @click="sortAppointments('date')" class="px-4 py-3 text-left text-sm font-medium cursor-pointer hover:bg-green-700 transition-colors">
                                    Date
                                    <ChevronDown :size="12" class="inline ml-1 transition-transform" :class="{ 'rotate-180': sortField === 'date' && sortDirection === 'desc' }" />
                                </th>
                                <th @click="sortAppointments('startTime')" class="px-4 py-3 text-left text-sm font-medium cursor-pointer hover:bg-green-700 transition-colors">
                                    Start Time
                                    <ChevronDown :size="12" class="inline ml-1 transition-transform" :class="{ 'rotate-180': sortField === 'startTime' && sortDirection === 'desc' }" />
                                </th>
                                <th @click="sortAppointments('branch')" class="px-4 py-3 text-left text-sm font-medium cursor-pointer hover:bg-green-700 transition-colors">
                                    Branch
                                    <ChevronDown :size="12" class="inline ml-1 transition-transform" :class="{ 'rotate-180': sortField === 'branch' && sortDirection === 'desc' }" />
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="appointment in sortedAppointments" :key="appointment.patientName" class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-900">{{ appointment.patientName }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ appointment.date }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ appointment.startTime }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ appointment.branch }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Custom styles for the dashboard */
.bg-green-800 {
    background-color: #1e4f4f;
}

.bg-green-700 {
    background-color: #2d6a6a;
}

.hover\:bg-green-700:hover {
    background-color: #2d6a6a;
}

/* Chart styles */
.chart-bar {
    transition: height 0.3s ease;
}
</style>
