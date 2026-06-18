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
  user: Object,
  roles: { type: Array, default: () => [] },
} );

const form = useForm ( {
  role: props.user?.role || null,
  first_name: props.user?.first_name || '',
  last_name: props.user?.last_name || '',
  email: props.user?.email || '',
  password: '',
  password_confirmation: '',
} );

const submit = () => {
  form.put ( route ( 'users.update', props.user.id ) );
};
</script>

<template>
  <AppLayout>
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold">{{ user.full_name }}</h1>
        <p class="text-muted-foreground">{{ user.email }}</p>
        <p class="text-muted-foreground">{{ user.role }}</p>
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
            <div class="grid gap-4 sm:grid-cols-2">
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
            </div>

            <!-- Email & role -->
            <div class="space-y-6">
              <div class="grid gap-4 sm:grid-cols-2">
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
                          :key="role.value"
                          :value="role.value"
                      >
                        {{ role.label }}
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
              v-if="props.user"
              model_type="user"
              :model_id="props.user.id"
              :current_image="props.user.avatar_url"
              :name="props.user.full_name"
          />
        </div>
        <!-- Footer -->
        <div class="flex items-center justify-end gap-3 border-t p-6">
          <Button
              type="submit"
              :disabled="form.processing"
          >Save User
          </Button>
        </div>
      </form>
    </Card>
  </AppLayout>
</template>

<style scoped>

</style>
