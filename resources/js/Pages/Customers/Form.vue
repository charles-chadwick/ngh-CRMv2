<script setup>
import AppLayout from "../../Layouts/AppLayout.vue";
import Input from "@/Components/UI/input/Input.vue";
import { Button } from "@/Components/UI/button/index.js";
import { Label } from "@/Components/UI/label/index.js";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/Components/UI/select/index.js";
import Avatar from "@/Components/Avatar.vue";
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  customer: Object,
  roles: { type: Array, default: () => [] },
});

const form = useForm({
  first_name: props.customer?.first_name || '',
  last_name: props.customer?.last_name || '',
  email: props.customer?.email || '',
  role: props.customer?.role || '',
  password: '',
  password_confirmation: '',
});

const submit = () => {
  form.put(route('customers.update', props.customer.id));
};
</script>

<template>
  <AppLayout>
    <div class="mx-auto w-full max-w-2xl px-4 py-8">
      <form
          class="rounded-xl border bg-card text-card-foreground shadow-sm"
          @submit.prevent="submit"
      >
        <!-- Header -->
        <div class="flex items-center gap-4 border-b p-6">
          <Avatar :src="props.customer?.avatar_url"/>
          <div>
            <h1 class="text-lg font-semibold leading-none">
              {{ props.customer ? 'Edit Customer' : 'New Customer' }}
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
              Update the customer's details and access.
            </p>
          </div>
        </div>

        <!-- Body -->
        <div class="space-y-6 p-6">
          <!-- Name row -->
          <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-2">
              <Label for="first_name">First Name</Label>
              <Input
                  id="first_name"
                  type="text"
                  placeholder="First Name"
                  v-model="form.first_name"
              />
              <p v-if="form.errors.first_name" class="text-sm text-destructive">
                {{ form.errors.first_name }}
              </p>
            </div>

            <div class="space-y-2">
              <Label for="last_name">Last Name</Label>
              <Input
                  id="last_name"
                  type="text"
                  placeholder="Last Name"
                  v-model="form.last_name"
              />
              <p v-if="form.errors.last_name" class="text-sm text-destructive">
                {{ form.errors.last_name }}
              </p>
            </div>
          </div>

          <!-- Email -->
          <div class="space-y-2">
            <Label for="email">Email</Label>
            <Input
                id="email"
                type="email"
                placeholder="name@example.com"
                v-model="form.email"
            />
            <p v-if="form.errors.email" class="text-sm text-destructive">
              {{ form.errors.email }}
            </p>
          </div>

          <!-- Role -->
          <div class="space-y-2">
            <Label for="role">Role</Label>
            <Select v-model="form.role">
              <SelectTrigger id="role" class="w-full">
                <SelectValue placeholder="Select a role" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="role in props.roles" :key="role" :value="role">
                  {{ role }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-if="form.errors.role" class="text-sm text-destructive">
              {{ form.errors.role }}
            </p>
          </div>

          <!-- Password row -->
          <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-2">
              <Label for="password">Password</Label>
              <Input
                  id="password"
                  type="password"
                  placeholder="••••••••"
                  v-model="form.password"
              />
              <p v-if="form.errors.password" class="text-sm text-destructive">
                {{ form.errors.password }}
              </p>
            </div>

            <div class="space-y-2">
              <Label for="password_confirmation">Confirm Password</Label>
              <Input
                  id="password_confirmation"
                  type="password"
                  placeholder="••••••••"
                  v-model="form.password_confirmation"
              />
            </div>
          </div>
          <p class="text-sm text-muted-foreground">
            Leave the password fields blank to keep the current password.
          </p>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-end gap-3 border-t p-6">
          <Button type="submit" :disabled="form.processing">Save Customer</Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<style scoped>

</style>
