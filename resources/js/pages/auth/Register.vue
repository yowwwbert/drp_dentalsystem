<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Eye, EyeOff } from 'lucide-vue-next';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { route } from 'ziggy-js';
import { ref, computed, watch } from 'vue';

const form = useForm({
    first_name: '',
    middle_name: '',
    last_name: '',
    suffix: '',
    age: '',
    birth_date: '',
    religion: '',
    sex: '',
    occupation: '',
    email_address: '',
    phone_number: '',
    address: '',
    user_type: 'Patient',
    status: 'Active',
    valid_id: null,
    password: '',
    password_confirmation: '',
    guardian_first_name: '',
    guardian_last_name: '',
    guardian_relationship: '',
    guardian_phone_number: '',
    guardian_email_address: '',
    guardian_valid_id: null,
    contact_information: '',
});

// Password strength checker
const passwordConditions = ref({
    length: false, // At least 8 characters
    uppercase: false, // At least one uppercase letter
    lowercase: false, // At least one lowercase letter
    number: false, // At least one number
    special: false, // At least one special character
});

// Check if all conditions are met
const isPasswordValid = computed(() => {
    return Object.values(passwordConditions.value).every(condition => condition);
});

const phoneRegex = /^(\+63|0)\d{10}$/;
const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/; // Basic email validation regex
const showPassword = ref(false);
const showConfirmPassword = ref(false);
const isPasswordFocused = ref(false);

function computeAge() {
    if (!form.birth_date) {
        form.age = '';
        return;
    }
    const birthDate = new Date(form.birth_date);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    form.age = age.toString();
}

function userOccupation() {
    form.occupation = form.user_type === 'Patient' ? form.occupation : form.user_type;
}

function userContactInformation() {
    // Ensure at least one of phone_number or email_address is provided
    if (!form.phone_number && !form.email_address) {
        form.errors.contact_information = 'Please provide either a phone number or an email address.';
        return false;
    }
    // Set empty field to 'N/A' if the other is provided
    if (!form.phone_number && form.email_address) {
        form.phone_number = 'N/A';
    } else if (!form.email_address && form.phone_number) {
        form.email_address = 'N/A';
    }
    return true;
}

watch(() => form.birth_date, () => {
    computeAge();
});

watch(() => form.password, (newPassword) => {
    passwordConditions.value = {
        length: newPassword.length >= 8,
        uppercase: /[A-Z]/.test(newPassword),
        lowercase: /[a-z]/.test(newPassword),
        number: /\d/.test(newPassword),
        special: /[!@#$%^&*.[\]?.,;:'"_+\-=/()|{}<>~]/.test(newPassword),
    };
});


const submit = () => {
    computeAge();
    userOccupation();
    if (!userContactInformation()) {
        return;
    }
    console.log('Submitting form with data:', form.data());

    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
            console.log('Form submission finished', form.errors);
        },
        onError: (errors) => {
            console.error('Form submission errors:', errors);
        },
    });
};

// Check if user is under 18
const isUnder18 = computed(() => Number(form.age) < 18 && form.age !== '');
</script>

<template>
    <AuthBase title="Create an account" description="Enter your details below to create your account">

        <Head title="Register" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <span class="text-red-600 italic text-sm">Fields marked with an asterisk (*) are required.</span>
                <!-- Name fields in a grid -->
                <div class="grid gap-2">
                    <Label for="user_type">User Type</Label>
                    <select id="user_type" v-model="form.user_type" @change="userOccupation"
                        class="h-10 rounded-md border border-input bg-background px-3 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        :tabindex="0">
                        <option value="Patient">Patient</option>
                        <option value="Owner">Owner</option>
                        <option value="Dentist">Dentist</option>
                        <option value="Staff">Staff</option>
                    </select>
                    <InputError :message="form.errors.user_type" />

                </div>
                <h1>Personal Information</h1>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="first_name">First Name <span class="text-red-600">*</span></Label>
                        <Input id="first_name" type="text" required autofocus :tabindex="1" autocomplete="given-name"
                            v-model="form.first_name" placeholder="First Name" />
                        <InputError :message="form.errors.first_name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="last_name">Last Name <span class="text-red-600">*</span></Label>
                        <Input id="last_name" type="text" required :tabindex="2" autocomplete="family-name"
                            v-model="form.last_name" placeholder="Last Name" />
                        <InputError :message="form.errors.last_name" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="middle_name">Middle Name</Label>
                            <Input id="middle_name" type="text" :tabindex="3" autocomplete="additional-name"
                                v-model="form.middle_name" placeholder="Middle Name" />
                            <InputError :message="form.errors.middle_name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="suffix">Suffix</Label>
                            <select id="suffix" :tabindex="4" v-model="form.suffix"
                                class="h-10 rounded-md border border-input bg-background px-3 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                autocomplete="honorific-suffix">
                                <option value="" selected>--</option>
                                <option value="Jr.">Jr.</option>
                                <option value="Sr.">Sr.</option>
                                <option value="II">II</option>
                                <option value="III">III</option>
                                <option value="IV">IV</option>
                                <option value="V">V</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                    <div class="grid gap-2">
                        <Label for="birth_date">Birth Date <span class="text-red-600">*</span></Label>
                        <Input id="birth_date" type="date" required :tabindex="5" autocomplete="bday"
                            v-model="form.birth_date" @change="computeAge" />
                        <InputError :message="form.errors.birth_date" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="gender">Gender <span class="text-red-600">*</span></Label>
                        <select name="gender" id="gender" required :tabindex="6" v-model="form.sex"
                            class="h-10 rounded-md border border-input bg-background px-3 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                            <option value="" disabled selected>--</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                        <InputError :message="form.errors.sex" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="religion">Religion</Label>
                        <Input id="religion" type="text" :tabindex="7" autocomplete="off" v-model="form.religion"
                            placeholder="Religion" />
                        <InputError :message="form.errors.religion" />
                    </div>
                </div>



                <div class="grid gap-2" v-if="form.user_type === 'Patient'">
                    <Label for="occupation">Occupation</Label>
                    <Input id="occupation" type="text" required :tabindex="8" autocomplete="off"
                        v-model="form.occupation" placeholder="Occupation" />
                    <InputError :message="form.errors.occupation" />
                </div>

                <div class="grid gap-2">
                    <Label for="address">Address <span class="text-red-600">*</span></Label>
                    <Input id="address" type="text" required :tabindex="9" autocomplete="street-address"
                        v-model="form.address" placeholder="Address" />
                    <InputError :message="form.errors.address" />
                </div>

                <div class="grid gap-2" v-if="form.user_type === 'Patient' && !isUnder18">
                    <Label for="valid_id">Valid ID <span class="text-red-600">*</span></Label>
                    <Input id="valid_id" type="file" required accept="image/*" :tabindex="10"
                        @change="form.valid_id = $event.target.files[0]" />
                    <InputError :message="form.errors.valid_id" />
                </div>

                <div v-if="isUnder18" class="grid gap-6">
                    <div class="grid gap-2">
                        <h1>Guardian Details</h1>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="guardian_first_name">Guardian First Name <span
                                    class="text-red-600">*</span></Label>
                            <Input id="guardian_first_name" type="text" required :tabindex="11"
                                autocomplete="given-name" v-model="form.guardian_first_name"
                                placeholder="Guardian First Name" />
                            <InputError :message="form.errors.guardian_first_name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="guardian_last_name">Guardian Last Name <span
                                    class="text-red-600">*</span></Label>
                            <Input id="guardian_last_name" type="text" required :tabindex="12"
                                autocomplete="family-name" v-model="form.guardian_last_name"
                                placeholder="Guardian Last Name" />
                            <InputError :message="form.errors.guardian_last_name" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="guardian_relationship">Relationship to Guardian <span
                                class="text-red-600">*</span></Label>
                        <Input id="guardian_relationship" type="text" required :tabindex="13"
                            v-model="form.guardian_relationship" placeholder="Relationship to Guardian" />
                        <InputError :message="form.errors.guardian_relationship" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="guardian_phone_number">Guardian Phone Number <span
                                    class="text-red-600">*</span></Label>
                            <Input id="guardian_phone_number" type="tel" required :tabindex="14" autocomplete="tel"
                                v-model="form.guardian_phone_number" placeholder="+63 912 345 6789" />
                            <InputError :message="form.errors.guardian_phone_number" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="guardian_email_address">Guardian Email Address <span
                                    class="text-red-600">*</span></Label>
                            <Input id="guardian_email_address" type="email" required :tabindex="15" autocomplete="email"
                                v-model="form.guardian_email_address" placeholder="guardian@example.com" />
                            <InputError :message="form.errors.guardian_email_address" />
                        </div>

                    </div>
                    <div class="grid gap-2" v-if="form.user_type === 'Patient'">
                        <Label for="guardian_valid_id">Valid ID <span class="text-red-600">*</span></Label>
                        <Input id="guardian_valid_id" type="file" required accept="image/*" :tabindex="16"
                            @change="form.guardian_valid_id = $event.target.files[0]" />
                        <InputError :message="form.errors.guardian_valid_id" />
                    </div>
                </div>


                <div class="grid gap-2">
                    <h1>Account Information</h1>
                </div>

                <div class="grid gap-2">
                    <Label for="phone_number">Phone number <span class="text-red-600">*</span></Label>
                    <Input
                        id="phone_number"
                        type="tel"
                        :tabindex="17"
                        autocomplete="tel"
                        v-model="form.phone_number"
                        placeholder="+63 912 345 6789 or 09123456789"
                        @blur="
                            () => {
                                if (form.phone_number && !phoneRegex.test(form.phone_number)) {
                                    form.errors.phone_number = 'Please enter a valid Philippine phone number.';
                                } else {
                                    form.errors.phone_number = undefined;
                                }
                            }
                        "
                    />
                    <InputError :message="form.errors.phone_number" />
                </div>

                <div class="grid gap-2">
                    <Label for="email_address">Email Address <span class="text-red-600">*</span></Label>
                    <Input id="email_address" type="email" :tabindex="18" autocomplete="email_address"
                        v-model="form.email_address" placeholder="email@example.com" 
                        @blur="
                            () => {
                                if (form.email_address && !emailRegex.test(form.email_address)) {
                                    form.errors.email_address = 'Please enter a valid email address.';
                                } else {
                                    form.errors.email_address = undefined;
                                }
                            }
                        "
                    />
                    <InputError :message="form.errors.email_address" />
                </div>
                <InputError :message="form.errors.contact_information" />

                <div class="grid gap-2">
                    <Label for="password">Password <span class="text-red-600">*</span></Label>
                    <div class="relative">
                        <Input id="password" :type="showPassword ? 'text' : 'password'" required :tabindex="19"
                            autocomplete="new-password" v-model="form.password" placeholder="Password"
                            @focus="isPasswordFocused = true" @blur="isPasswordFocused = false" class="pr-10" />
                        <button type="button" @click="showPassword = !showPassword" @mousedown.prevent
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-white hover:text-gray-400">
                            <Eye v-if="!showPassword" class="h-5 w-5" />
                            <EyeOff v-else class="h-5 w-5" />
                        </button>
                    </div>
                    <InputError :message="form.errors.password" />
                    <!-- Password Strength Checker -->
                    <div v-if="isPasswordFocused || form.password" class="text-sm text-white mt-2">
                        <p>Password must include:</p>
                        <ul class="list-disc pl-5">
                            <li
                                :class="{ 'text-green-600': passwordConditions.length, 'text-red-600': !passwordConditions.length }">
                                At least 8 characters
                            </li>
                            <li
                                :class="{ 'text-green-600': passwordConditions.uppercase, 'text-red-600': !passwordConditions.uppercase }">
                                At least one uppercase letter
                            </li>
                            <li
                                :class="{ 'text-green-600': passwordConditions.lowercase, 'text-red-600': !passwordConditions.lowercase }">
                                At least one lowercase letter
                            </li>
                            <li
                                :class="{ 'text-green-600': passwordConditions.number, 'text-red-600': !passwordConditions.number }">
                                At least one number
                            </li>
                            <li
                                :class="{ 'text-green-600': passwordConditions.special, 'text-red-600': !passwordConditions.special }">
                                At least one special character
                            </li>
                        </ul>
                        <p v-if="isPasswordValid" class="text-green-600 font-semibold">Password meets all requirements!
                        </p>
                    </div>
                </div>

                <div class="grid gap-2" v-if="isPasswordValid">
                    <Label for="password_confirmation">Confirm password <span class="text-red-600">*</span></Label>
                    <div class="relative">
                        <Input id="password_confirmation" :type="showConfirmPassword ? 'text' : 'password'" required :tabindex="20"
                            autocomplete="new-password" v-model="form.password_confirmation"
                            placeholder="Confirm password" class="pr-10" />
                        <button type="button" @click="showConfirmPassword = !showConfirmPassword" @mousedown.prevent
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-white hover:text-gray-400">
                            <Eye v-if="!showConfirmPassword" class="h-5 w-5" />
                            <EyeOff v-else class="h-5 w-5" />
                        </button>
                    </div>
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <Button type="submit" class="mt-2 w-full" :tabindex="21" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    <span v-else>Create account</span>
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink :href="route('login')" class="underline underline-offset-4" :tabindex="22">Log in</TextLink>
            </div>
        </form>
    </AuthBase>
</template>