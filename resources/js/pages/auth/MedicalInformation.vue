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

const form = useForm({
  previous_dentist: '',
  last_dental_visit: '',
  physician_name: '',
  physician_address: '',
  physician_contact: '',
  physician_specialty: '',
  under_medication: 'false', // stored as string initially
  congenital_abnormalities: 'false',
});

const submit = () => {

  form.post(route('medical-information.store'), {
    onFinish: () => {
      console.log('Form submission finished', form.errors);
    },
    onError: (errors) => {
      console.error('Form submission errors:', errors);
    },
  });
};
</script>

<template>
  <AuthBase title="Create an account" description="Enter your medical information to create an account.">
    <Head title="Medical Information" />

    <form @submit.prevent="submit" class="flex flex-col gap-6">
      <div class="grid grid-cols-2 gap-4">
        <div class="grid gap-2">
          <Label for="previous_dentist">Previous Dentist</Label>
          <Input id="previous_dentist" type="text" v-model="form.previous_dentist" placeholder="Previous Dentist" />
          <InputError :message="form.errors.previous_dentist" />
        </div>
        <div class="grid gap-2">
          <Label for="last_dental_visit">Last Dental Visit</Label>
          <Input id="last_dental_visit" type="date" v-model="form.last_dental_visit" />
          <InputError :message="form.errors.last_dental_visit" />
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div class="grid gap-2">
          <Label for="physician_name">Physician Name</Label>
          <Input id="physician_name" type="text" v-model="form.physician_name" placeholder="Physician Name" />
          <InputError :message="form.errors.physician_name" />
        </div>
        <div class="grid gap-2">
          <Label for="physician_contact">Physician Contact</Label>
          <Input id="physician_contact" type="text" v-model="form.physician_contact" placeholder="+1234567890" />
          <InputError :message="form.errors.physician_contact" />
        </div>
      </div>

      <div class="grid gap-2">
        <Label for="physician_address">Physician Address</Label>
        <Input id="physician_address" type="text" v-model="form.physician_address" placeholder="Physician Address" />
        <InputError :message="form.errors.physician_address" />
      </div>

      <div class="grid gap-2">
        <Label for="physician_specialty">Physician Specialty</Label>
        <Input id="physician_specialty" type="text" v-model="form.physician_specialty" placeholder="Physician Specialty" />
        <InputError :message="form.errors.physician_specialty" />
      </div>

      <!-- Radio buttons for under_medication -->
      <div class="grid gap-2">
        <Label>Under Medication</Label>
        <div class="flex gap-4">
          <label class="flex items-center gap-1">
            <input type="radio" value="true" v-model="form.under_medication" name="under_medication" />
            Yes
          </label>
          <label class="flex items-center gap-1">
            <input type="radio" value="false" v-model="form.under_medication" name="under_medication" />
            No
          </label>
        </div>
        <InputError :message="form.errors.under_medication" />
      </div>

      <!-- Radio buttons for congenital_abnormalities -->
      <div class="grid gap-2">
        <Label>Congenital Abnormalities</Label>
        <div class="flex gap-4">
          <label class="flex items-center gap-1">
            <input type="radio" value="true" v-model="form.congenital_abnormalities" name="congenital_abnormalities" />
            Yes
          </label>
          <label class="flex items-center gap-1">
            <input type="radio" value="false" v-model="form.congenital_abnormalities" name="congenital_abnormalities" />
            No
          </label>
        </div>
        <InputError :message="form.errors.congenital_abnormalities" />
      </div>

      <Button type="submit" class="mt-2 w-full" :disabled="form.processing">
        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
        <span v-else>Submit</span>
      </Button>

      <div class="text-center text-sm text-muted-foreground">
        Already have an account?
        <TextLink :href="route('login')" class="underline underline-offset-4">Log in</TextLink>
      </div>
    </form>
  </AuthBase>
</template>
