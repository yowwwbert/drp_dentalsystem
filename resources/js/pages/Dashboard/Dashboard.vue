<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import {
    Bell,
    Clock,
    Plus,
    Calendar,
    CalendarCheck,
    CalendarX,
    CalendarMinus2,
    CheckCircle2,
    XCircle,
    X,
    ChevronDown,
    ChevronUp,
    CheckCheck,
    ArrowRight,
} from 'lucide-vue-next';
import axios from 'axios';
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
    dentist_name?: string;
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

interface Notification {
    id: string;
    data: {
        title: string;
        message: string;
        patient_name?: string;
        patient_id?: number;
        appointment_id?: number;
        url?: string;
        type: string;
    };
    read_at: string | null;
    created_at: string;
}

const page = usePage<{ auth: Auth }>();
const user = computed(() => page.props.auth.user);
const userType = computed(() => user.value?.user_type || 'User');
console.log('User Type:', userType.value);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
];

// State
const dashboardData = ref<DashboardData>({
    appointments: {
        scheduled: 0,
        completed: 0,
        cancelled: 0,
        overview: [],
    },
    scheduledAppointments: [],
});

// Notifications State
const notifications = ref<Notification[]>([]);
const showNotifications = ref(false);
const activeTab = ref<'all' | 'unread' | 'read'>('all');
const unreadCount = computed(() => notifications.value.filter(n => !n.read_at).length);

const filteredNotifications = computed(() => {
    if (activeTab.value === 'all') return notifications.value;
    if (activeTab.value === 'unread') return notifications.value.filter(n => !n.read_at);
    return notifications.value.filter(n => n.read_at);
});

// Fetch dashboard and notifications data
onMounted(async () => {
    try {
        const response = await axios.get(route('dashboard.data'));
        dashboardData.value = {
            ...response.data,
            scheduledAppointments: Array.isArray(response.data.scheduledAppointments)
                ? response.data.scheduledAppointments
                : [],
        };
    } catch (error) {
        console.error('Error fetching dashboard data:', error);
        dashboardData.value.scheduledAppointments = [];
    }

    // Fetch notifications
    try {
        const notifResponse = await axios.get('/notifications');
        notifications.value = Array.isArray(notifResponse.data)
            ? notifResponse.data
            : [];
    } catch (error) {
        console.error('Error fetching notifications:', error);
        notifications.value = [];
    }
});

// Mark notification as read
const markAsRead = async (notificationId: string) => {
    try {
        await axios.post(`/notifications/${notificationId}/read`);
        notifications.value = notifications.value.map(n =>
            n.id === notificationId ? { ...n, read_at: new Date().toISOString() } : n
        );
    } catch (err) {
        console.error('Error marking notification as read:', err);
    }
};

// Mark all as read
const markAllAsRead = async () => {
    try {
        await axios.post('/notifications/mark-all-read');
        notifications.value = notifications.value.map(n => ({ ...n, read_at: new Date().toISOString() }));
    } catch (err) {
        console.error('Error marking all notifications as read:', err);
    }
};

// Chart data
const chartData = computed(() => dashboardData.value.appointments.overview);
const maxChartValue = computed(() => {
    const max = Math.max(
        ...chartData.value.flatMap(data => [data.completed || 0, data.cancelled || 0, data.scheduled || 0]),
        1
    );
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
    const appointments = Array.isArray(dashboardData.value.scheduledAppointments)
        ? [...dashboardData.value.scheduledAppointments]
        : [];
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
        return sortDirection.value === 'asc' ? aValue > bValue ? 1 : -1 : aValue < bValue ? -1 : 1;
    });
});

// User details
const userFirstName = computed(() => user.value?.first_name || 'User');
const userPosition = computed(() => user.value?.user_type || 'User');

// Permissions
const canAddAppointments = computed(() => ['Owner', 'Staff', 'Patient'].includes(userType.value));
const isPatient = computed(() => userType.value === 'Patient');

const appointmentListRoute = (status?: string) => {
    const basePath = userType.value === 'Patient' ? '/dashboard' : `/dashboard/${userType.value.toLowerCase()}/appointments/AppointmentList`;
    return status ? `${basePath}?status=${encodeURIComponent(status)}` : basePath;
};

// Get notification icon
const getNotificationIcon = (type: string) => {
    switch (type) {
        case 'appointment.created':
            return CalendarCheck;
        case 'appointment.cancelled':
            return CalendarX;
        case 'appointment.rescheduled':
            return CalendarMinus2;
        default:
            return Bell;
    }
};

// Format notification time
const formatTime = (timestamp: string) => {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now.getTime() - date.getTime();
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    if (minutes < 1) return 'Just now';
    if (minutes < 60) return `${minutes}m ago`;
    if (hours < 24) return `${hours}h ago`;
    if (days < 7) return `${days}d ago`;
    return date.toLocaleDateString();
};

// Handle notification click
const handleNotificationClick = async (notification: Notification) => {
    if (!notification.read_at) {
        await markAsRead(notification.id);
    }
    if (notification.data.url) {
        router.visit(notification.data.url);
    }
};
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout>
        <div class="fixed inset-0 -z-10 bg-gray-50 dark:bg-neutral-950"></div>
        <div class="space-y-5 pb-8">
            <div
                class="relative overflow-hidden bg-[#1e4f4f] dark:bg-neutral-900 dark:border dark:border-neutral-800 rounded-2xl shadow-lg">
                <div class="relative p-8">
                    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-1 h-12 bg-white/80 dark:bg-emerald-500 rounded-full"></div>
                                <div>
                                    <h1 class="text-4xl font-bold text-white dark:text-neutral-100 mb-1">Dashboard</h1>
                                    <p class="text-white/70 dark:text-neutral-400 text-md">Welcome back, <span
                                            class="font-semibold text-white dark:text-neutral-200">{{ userFirstName
                                            }}</span></p>
                                </div>
                            </div>
                            <div
                                class="flex flex-wrap items-center gap-4 text-md text-white/80 dark:text-neutral-400 ml-4">
                                <div
                                    class="flex items-center gap-2 bg-white/10 dark:bg-neutral-800 backdrop-blur-sm px-3 py-1.5 rounded-lg">
                                    <Calendar :size="14" class="text-white dark:text-neutral-300" />
                                    <span>{{ new Date().toLocaleDateString('en-US', {
                                        weekday: 'long', year: 'numeric',
                                        month: 'long', day: 'numeric'
                                    }) }}</span>
                                </div>
                                <div
                                    class="flex items-center gap-2 bg-white/10 dark:bg-neutral-800 backdrop-blur-sm px-3 py-1.5 rounded-lg">
                                    <Clock :size="14" class="text-white dark:text-neutral-300" />
                                    <span>{{ new Date().toLocaleTimeString('en-US', {
                                        hour: '2-digit', minute: '2-digit'
                                    }) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div
                                class="flex items-center gap-4 bg-white dark:bg-neutral-800 dark:border dark:border-neutral-700 px-5 py-3 rounded-xl shadow-lg">
                                <div
                                    class="w-12 h-12 bg-[#1e4f4f] dark:bg-emerald-600 rounded-xl flex items-center justify-center shadow-md">
                                    <span class="text-white text-lg font-bold">{{
                                        userFirstName.charAt(0).toUpperCase()
                                        }}</span>
                                </div>
                                <div>
                                    <p class="text-md font-bold text-gray-900 dark:text-neutral-100">{{ userFirstName }}
                                    </p>
                                    <p class="text-sm text-[#1e4f4f] dark:text-emerald-400 font-semibold">{{
                                        userPosition }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats and Quick Actions Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- Stats Cards Column (Spans 2 columns) -->
                <div class="lg:col-span-2 space-y-5">
                    <!-- Stats Cards Row -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Scheduled Card -->
                        <div
                            class="group relative overflow-hidden bg-white dark:bg-neutral-900 dark:border dark:border-neutral-800 rounded-xl shadow-md hover:shadow-lg transition-all duration-300">
                            <div class="relative p-5">
                                <div class="flex items-start justify-between mb-3">
                                    <div>
                                        <p
                                            class="text-sm font-bold text-[#1e4f4f]/70 dark:text-neutral-400 mb-2 uppercase tracking-wider">
                                            Scheduled</p>
                                        <div class="text-3xl font-bold text-[#1e4f4f] dark:text-emerald-400">
                                            {{ dashboardData.appointments.scheduled }}
                                        </div>
                                    </div>
                                    <div
                                        class="p-2 rounded-full bg-[#1e4f4f] dark:bg-emerald-600 shadow-lg group-hover:scale-110 transition-transform">
                                        <Clock :size="20" class="text-white" />
                                    </div>
                                </div>
                            </div>
                            <Link :href="appointmentListRoute('Scheduled')"
                                class="block bg-[#1e4f4f] dark:bg-emerald-600 hover:bg-[#26605f] dark:hover:bg-emerald-700 text-white transition-all rounded-b-xl">
                            <div class="px-5 py-2.5 flex items-center justify-center gap-2 font-semibold text-sm">
                                View Details
                                <ArrowRight :size="14" class="group-hover:translate-x-1 transition-transform" />
                            </div>
                            </Link>
                        </div>

                        <!-- Completed Card -->
                        <div
                            class="group relative overflow-hidden bg-white dark:bg-neutral-900 dark:border dark:border-neutral-800 rounded-xl shadow-md hover:shadow-lg transition-all duration-300">
                            <div class="relative p-5">
                                <div class="flex items-start justify-between mb-3">
                                    <div>
                                        <p
                                            class="text-sm font-bold text-[#2d6a6a]/70 dark:text-neutral-400 mb-2 uppercase tracking-wider">
                                            Completed</p>
                                        <div class="text-3xl font-bold text-[#2d6a6a] dark:text-emerald-400">
                                            {{ dashboardData.appointments.completed }}
                                        </div>
                                    </div>
                                    <div
                                        class="p-2 rounded-full bg-[#2d6a6a] dark:bg-emerald-600 shadow-lg group-hover:scale-110 transition-transform">
                                        <CheckCircle2 :size="20" class="text-white" />
                                    </div>
                                </div>
                            </div>
                            <Link :href="appointmentListRoute('Completed')"
                                class="block bg-[#2d6a6a] dark:bg-emerald-600 hover:bg-[#3a8280] dark:hover:bg-emerald-700 text-white transition-all rounded-b-xl">
                            <div class="px-5 py-2.5 flex items-center justify-center gap-2 font-semibold text-sm">
                                View Details
                                <ArrowRight :size="14" class="group-hover:translate-x-1 transition-transform" />
                            </div>
                            </Link>
                        </div>

                        <!-- Cancelled Card -->
                        <div
                            class="group relative overflow-hidden bg-white dark:bg-neutral-900 dark:border dark:border-neutral-800 rounded-xl shadow-md hover:shadow-lg transition-all duration-300">
                            <div class="relative p-5">
                                <div class="flex items-start justify-between mb-3">
                                    <div>
                                        <p
                                            class="text-sm font-bold text-[#3E7F7B]/70 dark:text-neutral-400 mb-2 uppercase tracking-wider">
                                            Cancelled</p>
                                        <div class="text-3xl font-bold text-[#3E7F7B] dark:text-emerald-400">
                                            {{ dashboardData.appointments.cancelled }}
                                        </div>
                                    </div>
                                    <div
                                        class="p-2 rounded-full bg-[#3E7F7B] dark:bg-emerald-600 shadow-lg group-hover:scale-110 transition-transform">
                                        <XCircle :size="20" class="text-white" />
                                    </div>
                                </div>
                            </div>
                            <Link :href="appointmentListRoute('Cancelled')"
                                class="block bg-[#3E7F7B] dark:bg-emerald-600 hover:bg-[#4d9691] dark:hover:bg-emerald-700 text-white transition-all rounded-b-xl">
                            <div class="px-5 py-2.5 flex items-center justify-center gap-2 font-semibold text-sm">
                                View Details
                                <ArrowRight :size="14" class="group-hover:translate-x-1 transition-transform" />
                            </div>
                            </Link>
                        </div>
                    </div>

                    <!-- Quick Action Card -->
                    <Link v-if="canAddAppointments" href="/appointment"
                        class="group block relative overflow-hidden bg-white dark:bg-neutral-900 dark:border-2 dark:border-neutral-700 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 border-2 border-dashed border-[#1e4f4f]/30 dark:border-neutral-700 hover:border-[#1e4f4f] dark:hover:border-emerald-600 hover:scale-[1.01]">
                    <div class="relative p-5 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div
                                class="w-12 h-12 bg-[#1e4f4f] dark:bg-emerald-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                                <Plus :size="24" class="text-white" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-neutral-100 mb-1">Create New
                                    Appointment</h3>
                                <p class="text-sm text-gray-600 dark:text-neutral-400">Schedule a new appointment for
                                    your patients</p>
                            </div>
                        </div>
                        <ArrowRight :size="24"
                            class="text-[#1e4f4f] dark:text-emerald-400 group-hover:translate-x-2 transition-transform" />
                    </div>
                    </Link>

                    <!-- Upcoming Appointments Table -->
                    <div
                        class="bg-white dark:bg-neutral-900 dark:border dark:border-neutral-800 rounded-xl shadow-md overflow-hidden flex flex-col">
                        <div class="p-5 border-b border-gray-200 dark:border-neutral-800">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-neutral-100 mb-1">Upcoming
                                        Appointments</h3>
                                    <p class="text-sm text-gray-500 dark:text-neutral-400">{{ sortedAppointments.length
                                        }} scheduled</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <Calendar :size="18" class="text-[#1e4f4f] dark:text-emerald-400" />
                                    <Link :href="appointmentListRoute()"
                                        class="text-sm font-semibold text-white bg-[#1e4f4f] dark:bg-emerald-600 hover:bg-[#26605f] dark:hover:bg-emerald-700 flex items-center gap-2 px-4 py-2 rounded-lg transition-all shadow-sm">
                                    View All
                                    <ArrowRight :size="14" />
                                    </Link>
                                </div>
                            </div>
                        </div>
                        <div class="overflow-x-auto flex-1">
                            <div class="max-h-[400px] overflow-y-auto custom-scrollbar">
                                <table class="w-full">
                                    <thead
                                        class="bg-[#1e4f4f] dark:bg-emerald-600 text-white sticky top-0 z-10 shadow-md">
                                        <tr>
                                            <th @click="sortAppointments('patient_name')"
                                                class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider cursor-pointer hover:bg-[#26605f] dark:hover:bg-emerald-700 transition-colors">
                                                <div class="flex items-center gap-2">
                                                    Patient Name
                                                    <component
                                                        :is="sortField === 'patient_name' && sortDirection === 'asc' ? ChevronUp : ChevronDown"
                                                        :size="14" />
                                                </div>
                                            </th>
                                            <th @click="sortAppointments('schedule_date')"
                                                class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider cursor-pointer hover:bg-[#26605f] dark:hover:bg-emerald-700 transition-colors">
                                                <div class="flex items-center gap-2">
                                                    Date & Time
                                                    <component
                                                        :is="sortField === 'schedule_date' && sortDirection === 'asc' ? ChevronUp : ChevronDown"
                                                        :size="14" />
                                                </div>
                                            </th>
                                            <th @click="sortAppointments('branch_name')"
                                                class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider cursor-pointer hover:bg-[#26605f] dark:hover:bg-emerald-700 transition-colors">
                                                <div class="flex items-center gap-2">
                                                    Branch
                                                    <component
                                                        :is="sortField === 'branch_name' && sortDirection === 'asc' ? ChevronUp : ChevronDown"
                                                        :size="14" />
                                                </div>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-neutral-800">
                                        <tr v-for="(appointment, idx) in sortedAppointments" :key="idx" :class="[
                                            'transition-all hover:bg-[#1e4f4f]/5 dark:hover:bg-neutral-800',
                                            idx % 2 === 0 ? 'bg-white dark:bg-neutral-900' : 'bg-gray-50 dark:bg-neutral-900/50'
                                        ]">
                                            <td class="px-5 py-3">
                                                <div class="flex items-center gap-3">
                                                    <div
                                                        class="w-9 h-9 bg-[#1e4f4f] dark:bg-emerald-600 rounded-lg flex items-center justify-center text-white text-sm font-bold shadow-md">
                                                        {{ appointment.patient_name.charAt(0).toUpperCase() }}
                                                    </div>
                                                    <div class="text-sm font-bold text-gray-900 dark:text-neutral-100">
                                                        {{ appointment.patient_name }}</div>
                                                </div>
                                            </td>
                                            <td class="px-5 py-3">
                                                <div class="text-sm font-bold text-gray-900 dark:text-neutral-100">{{
                                                    appointment.schedule_date }}</div>
                                                <div
                                                    class="text-xs text-gray-500 dark:text-neutral-400 font-semibold mt-0.5">
                                                    {{ appointment.start_time }}</div>
                                            </td>
                                            <td class="px-5 py-3">
                                                <span
                                                    class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold bg-[#1e4f4f]/10 dark:bg-emerald-600/20 text-[#1e4f4f] dark:text-emerald-400 border border-[#1e4f4f]/20 dark:border-emerald-600/30 shadow-sm">
                                                    {{ appointment.branch_name }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr v-if="!sortedAppointments.length">
                                            <td colspan="3"
                                                class="px-5 py-12 text-center bg-gray-50 dark:bg-neutral-900/50">
                                                <div
                                                    class="w-16 h-16 bg-white dark:bg-neutral-800 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-sm">
                                                    <Calendar :size="32" class="text-gray-300 dark:text-neutral-600" />
                                                </div>
                                                <p class="text-gray-600 dark:text-neutral-300 font-bold mb-1">No
                                                    scheduled appointments</p>
                                                <p class="text-sm text-gray-400 dark:text-neutral-500 mb-4">Upcoming
                                                    appointments will appear here</p>
                                                <Link v-if="canAddAppointments" href="/appointment"
                                                    class="inline-flex items-center gap-2 bg-[#1e4f4f] dark:bg-emerald-600 hover:bg-[#26605f] dark:hover:bg-emerald-700 text-white px-5 py-2.5 rounded-lg transition-all font-bold text-sm shadow-md hover:shadow-lg">
                                                <Plus :size="16" />
                                                Create New Appointment
                                                </Link>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notifications Panel (1 column) -->
                <div
                    class="bg-white dark:bg-neutral-900 dark:border dark:border-neutral-800 rounded-xl shadow-md overflow-hidden flex flex-col">
                    <div class="bg-[#1e4f4f] dark:bg-neutral-800 p-5">
                        <div class="flex justify-between items-center mb-4">
                            <div class="flex items-center gap-3">
                                <div class="p-2.5 bg-white/20 dark:bg-neutral-700 rounded-lg">
                                    <Bell :size="18" class="text-white dark:text-neutral-200" />
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-white dark:text-neutral-100">Notifications</h3>
                                    <p class="text-sm text-white/70 dark:text-neutral-400">{{ unreadCount }} unread
                                        messages</p>
                                </div>
                            </div>
                            <button v-if="unreadCount > 0" @click="markAllAsRead"
                                class="text-xs text-white hover:text-white/80 dark:text-neutral-300 dark:hover:text-neutral-100 font-semibold flex items-center gap-1.5 bg-white/20 dark:bg-neutral-700 hover:bg-white/30 dark:hover:bg-neutral-600 px-3 py-1.5 rounded-lg transition-all">
                                <CheckCheck :size="14" />
                                Mark all
                            </button>
                        </div>

                        <!-- Tabs -->
                        <div class="flex gap-2">
                            <button @click="activeTab = 'all'" :class="[
                                'px-3 py-2 font-semibold transition-all text-xs rounded-lg',
                                activeTab === 'all' ? 'bg-white dark:bg-neutral-700 text-[#1e4f4f] dark:text-neutral-100 shadow-md' : 'text-white/80 dark:text-neutral-400 hover:bg-white/10 dark:hover:bg-neutral-700'
                            ]">
                                All ({{ notifications.length }})
                            </button>
                            <button @click="activeTab = 'unread'" :class="[
                                'px-3 py-2 font-semibold transition-all text-xs rounded-lg',
                                activeTab === 'unread' ? 'bg-white dark:bg-neutral-700 text-[#1e4f4f] dark:text-neutral-100 shadow-md' : 'text-white/80 dark:text-neutral-400 hover:bg-white/10 dark:hover:bg-neutral-700'
                            ]">
                                Unread ({{ unreadCount }})
                            </button>
                        </div>
                    </div>

                    <!-- Notifications List -->
                    <div class="p-4 space-y-2 flex-1 bg-gray-50 dark:bg-neutral-950 overflow-y-auto custom-scrollbar"
                        style="max-height: calc(100vh - 280px);">
                        <div v-for="notification in filteredNotifications.slice(0, 5)" :key="notification.id"
                            @click="handleNotificationClick(notification)" :class="[
                                'p-3 rounded-xl transition-all border cursor-pointer hover:scale-[1.01]',
                                notification.read_at
                                    ? 'bg-white dark:bg-neutral-900 border-gray-200 dark:border-neutral-800 hover:border-gray-300 dark:hover:border-neutral-700 hover:shadow-md'
                                    : 'bg-[#1e4f4f]/5 dark:bg-emerald-600/10 border-[#1e4f4f]/40 dark:border-emerald-600/30 hover:border-[#1e4f4f] dark:hover:border-emerald-600 shadow-sm hover:shadow-md'
                            ]">
                            <div class="flex items-start gap-3">
                                <div :class="[
                                    'p-2 rounded-lg flex-shrink-0 shadow-sm',
                                    notification.read_at ? 'bg-gray-100 dark:bg-neutral-800' : 'bg-white dark:bg-neutral-800 border-2 border-[#1e4f4f]/50 dark:border-emerald-600/50'
                                ]">
                                    <component :is="getNotificationIcon(notification.data.type)" :size="16"
                                        :class="notification.read_at ? 'text-gray-500 dark:text-neutral-400' : 'text-[#1e4f4f] dark:text-emerald-400'" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-2 mb-1">
                                        <strong class="text-sm font-bold"
                                            :class="notification.read_at ? 'text-gray-700 dark:text-neutral-300' : 'text-[#1e4f4f] dark:text-emerald-400'">
                                            {{ notification.data.title }}
                                        </strong>
                                        <span v-if="!notification.read_at"
                                            class="w-2 h-2 bg-[#1e4f4f] dark:bg-emerald-500 rounded-full flex-shrink-0 mt-1 shadow-sm animate-pulse"></span>
                                    </div>
                                    <p class="text-sm leading-relaxed"
                                        :class="notification.read_at ? 'text-gray-600 dark:text-neutral-400' : 'text-gray-700 dark:text-neutral-300'">
                                        {{ notification.data.message }}
                                    </p>
                                    <span class="text-xs font-medium mt-1 block"
                                        :class="notification.read_at ? 'text-gray-400 dark:text-neutral-500' : 'text-[#1e4f4f]/70 dark:text-emerald-400/70'">
                                        {{ formatTime(notification.created_at) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div v-if="!filteredNotifications.length" class="text-center py-12">
                            <div
                                class="w-16 h-16 bg-gray-100 dark:bg-neutral-800 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                <Bell :size="32" class="text-gray-300 dark:text-neutral-600" />
                            </div>
                            <p class="text-gray-600 dark:text-neutral-300 font-bold text-sm mb-1">No {{ activeTab ===
                                'all' ? '' : activeTab }} notifications</p>
                            <p class="text-xs text-gray-400 dark:text-neutral-500">You're all caught up!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Custom Scrollbar */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f9fafb;
    border-radius: 10px;
}

.dark .custom-scrollbar::-webkit-scrollbar-track {
    background: #171717;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #1e4f4f;
    border-radius: 10px;
    transition: background 0.3s;
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #10b981;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #2d6a6a;
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #059669;
}

/* Firefox scrollbar */
.custom-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: #1e4f4f #f9fafb;
}

.dark .custom-scrollbar {
    scrollbar-color: #10b981 #171717;
}

/* Smooth animations */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.space-y-5>* {
    animation: fadeIn 0.5s ease-out forwards;
}

.space-y-5>*:nth-child(1) {
    animation-delay: 0.1s;
}

.space-y-5>*:nth-child(2) {
    animation-delay: 0.2s;
}

.space-y-5>*:nth-child(3) {
    animation-delay: 0.3s;
}
</style>