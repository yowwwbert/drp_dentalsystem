<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { ref, onMounted } from 'vue';
import axios from 'axios';

const { props } = usePage() as { props: { dentistId: string } };
const dentist = ref<Dentist | null>(null);
const appointments = ref<Appointment[]>([]);
const loading = ref(true);
const appointmentsLoading = ref(true);
const error = ref<string | null>(null);
const appointmentsError = ref<string | null>(null);

export interface BreadcrumbItem {
    title: string;
    href: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Dentist Records', href: '/dashboard/owner/records/DentistRecords' },
    { title: 'Dentist Information', href: '/' },
];

interface Dentist {
    dentist_id: string;
    first_name: string;
    last_name: string;
    middle_name: string | null;
    suffix: string | null;
    email_address: string;
    phone_number: string;
    dentist_type: string;
    branch_name: string;
    branchId: string | null;
    status: string;
    email_verified_at: string | null;
    phone_verified_at: string | null;
    religion: string | null;
    profile_picture: string | null;
    birth_date: string | null;
    sex: string | null;
}

interface Appointment {
  appointment_id: string;
  patient_id: string;
  patient: string;
  date: string;
  time: string; // Expected as HH:mm:ss from backend
  branch: string;
  services: string[];
  dentist: string;
  status: string;
  balance: number;
}

const form = useForm({
    dentist_id: '',
    first_name: '',
    last_name: '',
    middle_name: '',
    suffix: '',
    email_address: '',
    phone_number: '',
    dentist_type: '',
    branch_name: '',
    status: '',
    email_verified_at: null,
    phone_verified_at: null,
    occupation: '',
    religion: '',
    profile_picture: '',
    birth_date: '',
    sex: '',
    errors: {},
});

const fetchDentistDetail = async () => {
    try {
        loading.value = true;
        const response = await axios.get(route('owner.dentist.api.detail', { id: props.dentistId }));
        const data = response.data.dentist;
        dentist.value = data;
        form.dentist_id = data.dentist_id;
        form.first_name = data.first_name;
        form.last_name = data.last_name;
        form.middle_name = data.middle_name || '';
        form.suffix = data.suffix || '';
        form.email_address = data.email_address;
        form.phone_number = data.phone_number;
        form.dentist_type = data.dentist_type;
        form.branch_name = data.branch_name;
        form.status = data.status;
        form.email_verified_at = data.email_verified_at;
        form.phone_verified_at = data.phone_verified_at;
        form.occupation = data.occupation || '';
        form.religion = data.religion || '';
        form.profile_picture = data.profile_picture || '';
        form.birth_date = data.birth_date || '';
        form.sex = data.sex || '';
    } catch (err) {
        error.value = 'Failed to fetch dentist details';
        console.error('Error fetching dentist details:', err);
    } finally {
        loading.value = false;
    }
};

const fetchAppointments = async () => {
    try {
        appointmentsLoading.value = true;
        const response = await axios.get(route('dashboard.appointments'), {
            params: { dentist_id: props.dentistId }
        });
        appointments.value = response.data.appointments; 
    } catch (err) {
        appointmentsError.value = 'Failed to fetch appointments';
        console.error('Error fetching appointments:', err);
    } finally {
        appointmentsLoading.value = false;
    }
};

const submit = () => {
    form.patch(route('dentist.update', { id: props.dentistId }), {
        preserveScroll: true,
    });
};

// ✅ Date formatter
const formatDate = (date: string): string => {
  return date
    ? new Date(date).toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
      })
    : 'N/A';
};

// ✅ Time formatter
const formatTimeForDisplay = (time: string): string => {
  if (!time || time === 'N/A' || !time.match(/^\d{2}:\d{2}:\d{2}$/)) {
    return 'N/A';
  }
  return new Date(`1970-01-01T${time}`).toLocaleTimeString('en-US', {
    hour: 'numeric',
    minute: '2-digit',
  });
};

onMounted(() => {
    if (props.dentistId) {
        fetchDentistDetail();
        fetchAppointments();
    } else {
        error.value = 'No dentist ID provided';
        loading.value = false;
        appointmentsLoading.value = false;
    }
});
</script>

<template>
    <Head title="Dentist Details" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full px-10 pt-10">
            <Heading title="Dentist Details" description="View and update dentist information" />
        </div>
        <div class="flex flex-col md:flex-row gap-8 px-10 pt-4">
            <div class="flex-1 md:w-1/3">
                <div class="flex flex-col space-y-6">
                    <Label class="text-md font-medium mb-4">Personal Details</Label>
                    <form action="">
                        <div class="space-y-6">
                            <div class="flex gap-4">
                                <div class="flex-1">
                                    <Label for="first_name" class="font-normal">First Name</Label>
                                    <Input id="first_name" class="mt-1 block w-full" v-model="form.first_name" required
                                        autocomplete="given-name" placeholder="First Name" disabled />
                                    <InputError class="mt-2" :message="form.errors.first_name" />
                                </div>
                                <div class="flex-1">
                                    <Label for="last_name" class="font-normal">Last Name</Label>
                                    <Input id="last_name" class="mt-1 block w-full" v-model="form.last_name" required
                                        autocomplete="family-name" placeholder="Last Name" disabled />
                                    <InputError class="mt-2" :message="form.errors.last_name" />
                                </div>
                            </div>
                            <div class="flex gap-4">
                                <div class="flex-1">
                                    <Label for="middle_name" class="font-normal">Middle Name</Label>
                                    <Input id="middle_name" class="mt-1 block w-full" v-model="form.middle_name"
                                        autocomplete="additional-name" placeholder="Middle Name" disabled />
                                    <InputError class="mt-2" :message="form.errors.middle_name" />
                                </div>
                                <div class="flex-1">
                                    <Label for="suffix" class="font-normal">Suffix</Label>
                                    <Input id="suffix" class="mt-1 block w-full" v-model="form.suffix"
                                        autocomplete="honorific-suffix" placeholder="Suffix" disabled />
                                    <InputError class="mt-2" :message="form.errors.suffix" />
                                </div>
                            </div>
                            <div class="flex gap-4">
                                <div class="flex-1">
                                    <Label for="birth_date" class="font-normal">Birth Date</Label>
                                    <Input id="birth_date" type="date" class="mt-1 block w-full"
                                        v-model="form.birth_date" autocomplete="bday" placeholder="Birth Date"
                                        disabled />
                                    <InputError class="mt-2" :message="form.errors.birth_date" />
                                </div>
                                <div class="flex-1">
                                    <Label for="sex" class="font-normal">Sex</Label>
                                    <Input id="sex" class="mt-1 block w-full" v-model="form.sex" autocomplete="sex"
                                        placeholder="Sex" disabled />
                                    <InputError class="mt-2" :message="form.errors.sex" />
                                </div>
                            </div>
                            <div class="flex gap-4">
                                <div class="flex-1">
                                    <Label for="religion" class="font-normal">Religion</Label>
                                    <Input id="religion" class="mt-1 block w-full" v-model="form.religion"
                                        autocomplete="honorific-suffix" placeholder="Religion" disabled />
                                    <InputError class="mt-2" :message="form.errors.religion" />
                                </div>
                                <div class="flex-1">
                                    <Label for="status" class="font-normal">Status</Label>
                                    <Input id="status" class="mt-1 block w-full" v-model="form.status"
                                        autocomplete="status" placeholder="Status" />
                                    <InputError class="mt-2" :message="form.errors.status" />
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="flex-1 md:w-1/3">
                <div class="flex flex-col space-y-6">
                    <Label class="text-md font-medium mb-4">Contact Details</Label>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid gap-2">
                            <Label for="phone_number" class="font-normal">Phone Number</Label>
                            <Input id="phone_number" class="mt-1 block w-full" v-model="form.phone_number" required
                                autocomplete="phone_number" placeholder="+63 9123456789" />
                            <InputError class="mt-2" :message="form.errors.phone_number" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="email_address" class="font-normal">Email Address</Label>
                            <Input id="email_address" type="email" class="mt-1 block w-full"
                                v-model="form.email_address" required autocomplete="username"
                                placeholder="Email address" />
                            <InputError class="mt-2" :message="form.errors.email_address" />
                        </div>
                    </form>
                </div>
            </div>
            <div class="flex-1 md:w-1/3">
                <div class="mt-6 flex flex-col items-center justify-center p-6 rounded-lg shadow-md">
                    <Label class="text-md font-medium mb-4">Profile Picture</Label>
                    <div
                        class="w-32 h-32 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden mt-4 border-2 border-[#3E7F7B]">
                        <span v-if="!dentist?.profile_picture" class="text-4xl text-gray-400">No Image</span>
                        <img v-else :src="dentist.profile_picture" alt="Dentist Profile Picture"
                            class="w-full h-full object-cover" />
                    </div>
                </div>
            </div>
        </div>

        <div class="w-full px-10 pt-10">
            <Heading title="Dentist Appointments" description="View appointments for this dentist" />
            <div v-if="appointmentsLoading" class="text-center py-4">
                Loading appointments...
            </div>
            <div v-else-if="appointmentsError" class="text-center py-4 text-red-600">
                {{ appointmentsError }}
            </div>
            <table v-else class="w-full min-w-[900px] border-separate border-spacing-0">
                <thead>
                    <tr class="bg-darkGreen-900 text-white">
                        <th class="py-3 px-4 text-left font-semibold">Patient Name</th>
                        <th class="py-3 px-4 text-left font-semibold">Date</th>
                        <th class="py-3 px-4 text-left font-semibold">Time</th>
                        <th class="py-3 px-4 text-left font-semibold">Treatment</th>
                        <th class="py-3 px-4 text-left font-semibold">Status</th>
                        <th class="py-3 px-4 text-left font-semibold">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="appointment in appointments" :key="appointment.patient_id"
                        class="border-b border-gray-200 hover:bg-gray-100">
                        <td class="py-3 px-4">{{ appointment.patient || 'N/A' }}</td>
                        <td class="py-3 px-4">{{ formatDate(appointment.date) }}</td>
                        <td class="py-3 px-4">{{ formatTimeForDisplay(appointment.time) }}</td>
                        <td class="py-3 px-4">{{ appointment.services.join(', ') || 'N/A' }}</td>
                        <td class="py-3 px-4">{{ appointment.status || 'N/A' }}</td>
                        <td class="py-3 px-4">
                            <Button variant="outline" size="sm">View</Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
<style scoped> .bg-blue-600 { background-color: #2563eb; } .hover\:bg-blue-700:hover { background-color: #1d4ed8; } .bg-darkGreen-900 { background-color: #1e4f4f; } .bg-darkGreen-800 { background-color: #1a4545; } </style>