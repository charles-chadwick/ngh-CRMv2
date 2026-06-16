<script setup>
import AppLayout from "../../Layouts/AppLayout.vue";
import Input from "@/Components/UI/input/Input.vue";
import { Button } from "@/Components/UI/button/index.js";
import { Label } from "@/Components/UI/label/index.js";
import AvatarUpload from "@/Components/AvatarUpload.vue";
import { useForm } from '@inertiajs/vue3'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/Components/UI/select/index.js";
import { Card } from "@/Components/UI/card/index.js";

const props = defineProps ( {
  customer: Object,
  roles: { type: Array, default: () => [] },
} );

const form = useForm ( {
  role: props.customer?.role || '',
  prefix: props.customer?.prefix || '',
  suffix: props.customer?.suffix || '',
  title: props.customer?.title || '',
  first_name: props.customer?.first_name || '',
  last_name: props.customer?.last_name || '',
  email: props.customer?.email || '',
  password: '',
  password_confirmation: '',
} );

const submit = () => {
  form.put ( route ( 'customers.update', props.customer.id ) );
};
</script>

<template>
  <AppLayout>
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold">{{ customer.full_name }}</h1>
        <p class="text-muted-foreground">{{ customer.email }}</p>
        <p
            class="text-muted-foreground"
            v-html="customer.role"
        ></p>
      </div>

    </div>
    <Card class="px-6">
      <form
          @submit.prevent="submit"
      >
        <div class="flex flex-row justify-between gap-4">
          <!-- Body -->
          <div class="w-full space-y-6 p-6">

            <!-- Name row -->
            <div class="grid gap-4 sm:grid-cols-4">
              <div class="space-y-2">
                <Label for="prefix">Prefix</Label>
                <Input
                    id="prefix"
                    type="text"
                    placeholder="Prefix"
                    v-model="form.prefix"
                />
                <p
                    v-if="form.errors.prefix"
                    class="text-sm text-destructive"
                >
                  {{ form.errors.prefix }}
                </p>
              </div>
              <div class="space-y-2">
                <Label for="first_name">First Name</Label>
                <Input
                    id="first_name"
                    type="text"
                    placeholder="First Name"
                    class="focus:ring-primary focus:outline-2 focus:outline-primary"
                    v-model="form.first_name"
                />
                <p
                    v-if="form.errors.first_name"
                    class="text-sm text-destructive"
                >
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
                <p
                    v-if="form.errors.last_name"
                    class="text-sm text-destructive"
                >
                  {{ form.errors.last_name }}
                </p>
              </div>

              <div class="space-y-2">
                <Label for="suffix">Suffix</Label>
                <Input
                    id="suffix"
                    type="text"
                    placeholder="Suffix"
                    v-model="form.suffix"
                />
                <p
                    v-if="form.errors.suffix"
                    class="text-sm text-destructive"
                >
                  {{ form.errors.suffix }}
                </p>
              </div>
            </div>

            <!-- Email -->
            <div class="space-y-6">
              <div class="grid gap-4 sm:grid-cols-3">
                <div class="space-y-2">
                  <Label for="email">Email</Label>
                  <Input
                      id="email"
                      type="email"
                      placeholder="name@example.com"
                      v-model="form.email"
                  />
                  <p
                      v-if="form.errors.email"
                      class="text-sm text-destructive"
                  >
                    {{ form.errors.email }}
                  </p>
                </div>

                <!-- Role -->
                <div class="space-y-2">
                  <Label for="role">Role</Label>
                  <Select v-model="form.role">
                    <SelectTrigger
                        id="role"
                        class="w-full"
                    >
                      <SelectValue placeholder="Select a role" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                          v-for="role in props.roles"
                          :key="role"
                          :value="role"
                      >
                        {{ role }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                  <p
                      v-if="form.errors.role"
                      class="text-sm text-destructive"
                  >
                    {{ form.errors.role }}
                  </p>
                </div>

                <!-- Title -->
                <div class="space-y-2">
                  <Label for="title">Title</Label>
                  <Input
                      id="title"
                      type="text"
                      placeholder="Title"
                      v-model="form.title"
                  >

                  </Input>
                  <p
                      v-if="form.errors.title"
                      class="text-sm text-destructive"
                  >
                    {{ form.errors.title }}
                  </p>
                </div>
              </div>
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
                <p
                    v-if="form.errors.password"
                    class="text-sm text-destructive"
                >
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
          <!-- Avatar -->
          <AvatarUpload
              v-if="props.customer"
              model_type="customer"
              :model_id="props.customer.id"
              :current_image="props.customer.avatar_url"
              :name="props.customer.full_name"
          />
        </div>
        <!-- Footer -->
        <div class="flex items-center justify-end gap-3 border-t p-6">
          <Button
              type="submit"
              :disabled="form.processing"
          >Save Customer
          </Button>
        </div>
      </form>
    </Card>
  </AppLayout>
</template>

<style scoped>

</style>
