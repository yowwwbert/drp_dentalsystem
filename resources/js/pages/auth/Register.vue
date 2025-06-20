<script setup lang="ts">
import InputError from '../../components/InputError.vue';
import TextLink from '../../components/TextLink.vue';
import { Button } from '../../components/ui/button';
import { Input } from '../../components/ui/input';
import { Label } from '../../components/ui/label';
import AuthBase from '../../layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { route } from 'ziggy-js';
import { watch } from 'vue';
import { router } from '@inertiajs/vue3';


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
});


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

// Watch birth_date and compute age automatically
watch(() => form.birth_date, () => {
    computeAge();
});

function userOccupation() {
    return form.user_type === 'Patient'
        ? form.occupation
        : form.user_type;
}


const submit = () => {
    computeAge();
    userOccupation();
    console.log('Submitting form with data:', form.data());

    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation', 'valid_id');
            console.log('Form submission finished', form.errors);
        },
        onError: (errors) => {
            console.error('Form submission errors:', errors);
        },
    });
};
</script>

<template>
    <AuthBase title="Create an account" description="Enter your details below to create your account">
        <Head title="Register" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <!-- Name fields in a grid -->
                <div class="grid grid-cols-2 md:grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="first_name">First Name</Label>
                        <Input id="first_name" type="text" required autofocus :tabindex="1" autocomplete="given-name"
                            v-model="form.first_name" placeholder="First Name" />
                        <InputError :message="form.errors.first_name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="last_name">Last Name</Label>
                        <Input id="last_name" type="text" required :tabindex="2" autocomplete="family-name"
                            v-model="form.last_name" placeholder="Last Name" />
                        <InputError :message="form.errors.last_name" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <div class="grid grid-cols-2 md:grid-cols-2 gap-4">
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
                                <option value="" disabled selected>--</option>
                                <option value="Jr.">Jr.</option>
                                <option value="Sr.">Sr.</option>
                                <option value="II">II</option>
                                <option value="III">III</option>
                                <option value="IV">IV</option>
                                <option value="V">V</option>
                            </select>
                            <InputError :message="form.errors.suffix" />
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-3 md:grid-cols-3 gap-4">
                    <div class="grid gap-2">
                        <Label for="birth_date">Birth Date</Label>
                        <Input id="birth_date" type="date" required :tabindex="5" autocomplete="bday"
                            v-model="form.birth_date" @change="computeAge" />
                        <InputError :message="form.errors.birth_date" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="sex">Sex</Label>
                        <select name="sex" id="sex" required :tabindex="6" v-model="form.sex"
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
                        <Input id="religion" type="text" required :tabindex="7" autocomplete="off"
                            v-model="form.religion" placeholder="Religion" />
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
                    <Label for="address">Address</Label>
                    <Input id="address" type="text" required :tabindex="9" autocomplete="street-address"
                        v-model="form.address" placeholder="Address" />
                    <InputError :message="form.errors.address" />
                </div>

        
                <div class="grid gap-2">
                    <Label for="valid_id">Valid ID</Label>
                    <Input id="valid_id" type="file" accept="image/*" required :tabindex="10"
                        @change="form.valid_id = $event.target.files[0]" />
                    <InputError :message="form.errors.valid_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="phone_number">Phone number</Label>
                    <Input id="phone_number" type="tel" required :tabindex="11" autocomplete="tel"
                        v-model="form.phone_number" placeholder="+63 912 345 6789" />
                
                    <InputError :message="form.errors.phone_number" />
                </div>

                <div class="grid gap-2">
                    <Label for="email_address">Email address</Label>
                    <Input id="email_address" type="email" required :tabindex="12" autocomplete="email"
                        v-model="form.email_address" placeholder="email@example.com" />
                    <!-- For phone number verification -->

                    <InputError :message="form.errors.email_address" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <Input id="password" type="password" required :tabindex="13" autocomplete="new-password"
                        v-model="form.password" placeholder="Password" />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <Input id="password_confirmation" type="password" required :tabindex="14" autocomplete="new-password"
                        v-model="form.password_confirmation" placeholder="Confirm password" />
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <Button type="submit" class="mt-2 w-full" :tabindex="15" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    <span v-else>Create account</span>
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink :href="route('login')" class="underline underline-offset-4" :tabindex="16">Log in</TextLink>
            </div>
        </form>
    </AuthBase>
</template>