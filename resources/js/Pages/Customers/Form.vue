<script setup>
import AppLayout from "../../Layouts/AppLayout.vue";
import Input from "@/Components/UI/input/Input.vue";
import { Button } from "@/Components/UI/button/index.js";
import { Label } from "@/Components/UI/label/index.js";
import AvatarUpload from "@/Components/AvatarUpload.vue";
import { useForm } from '@inertiajs/vue3'
import { computed, nextTick, ref } from 'vue'
import { X } from "lucide-vue-next";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/Components/UI/select/index.js";
import { Card } from "@/Components/UI/card/index.js";

const props = defineProps ( {
  customer: Object,
  roles: { type: Array, default: () => [] },
  titles: { type: Array, default: () => [] },
} );

const form = useForm ( {
  customer_role_id: props.customer?.customer_role_id || null,
  prefix: props.customer?.prefix || '',
  suffix: props.customer?.suffix || '',
  titles: props.customer?.titles?.map ( title => title.id ) || [],
  first_name: props.customer?.first_name || '',
  last_name: props.customer?.last_name || '',
  email: props.customer?.email || '',
  password: '',
  password_confirmation: '',
} );

// Drives the dropdown itself. Each pick is moved into form.titles as a badge,
// then this resets so the trigger is ready for the next one.
const picked_title = ref ( null );

/** The picked titles, in the order they were added, for the badge list. */
const selected_titles = computed ( () => form.titles
    .map ( id => props.titles.find ( title => title.id === id ) )
    .filter ( Boolean ) );

/** Only offer titles the customer doesn't already have. */
const available_titles = computed ( () => props.titles
    .filter ( title => !form.titles.includes ( title.id ) ) );

const addTitle = ( id ) => {
  if ( id && !form.titles.includes ( id ) ) {
    form.titles.push ( id );
  }

  nextTick ( () => picked_title.value = null );
};

const removeTitle = ( id ) => {
  form.titles = form.titles.filter ( title_id => title_id !== id );
};

const submit = () => {
  if ( props.customer ) {
    form.put ( route ( 'customers.update', props.customer.id ) );

    return;
  }

  form.post ( route ( 'customers.store' ) );
};
</script>

<template>
  <AppLayout>
    <div class="flex items-center justify-between mb-6">
      <div v-if="props.customer">
        <h1 class="text-2xl font-bold">{{ customer.full_name }}</h1>
        <p class="text-muted-foreground">{{ customer.email }}</p>
        <p class="text-muted-foreground">{{ customer.role?.name }}</p>
      </div>
      <h1
          v-else
          class="text-2xl font-bold"
      >New Customer</h1>

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
                  <Select v-model="form.customer_role_id">
                    <SelectTrigger
                        id="role"
                        class="w-full"
                    >
                      <SelectValue placeholder="Select a role" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                          v-for="role in props.roles"
                          :key="role.id"
                          :value="role.id"
                      >
                        {{ role.name }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                  <p
                      v-if="form.errors.customer_role_id"
                      class="text-sm text-destructive"
                  >
                    {{ form.errors.customer_role_id }}
                  </p>
                </div>

                <!-- Titles -->
                <div class="space-y-2">
                  <Label for="titles">Titles</Label>
                  <Select
                      v-model="picked_title"
                      :disabled="available_titles.length === 0"
                      @update:model-value="addTitle"
                  >
                    <SelectTrigger
                        id="titles"
                        class="w-full"
                    >
                      <span class="text-muted-foreground">
                        {{ available_titles.length ? 'Add a title' : 'All titles added' }}
                      </span>
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                          v-for="title in available_titles"
                          :key="title.id"
                          :value="title.id"
                      >
                        {{ title.title }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                  <!-- One dismissable badge per picked title. -->
                  <div
                      v-if="selected_titles.length"
                      class="flex flex-wrap gap-2"
                  >
                    <span
                        v-for="title in selected_titles"
                        :key="title.id"
                        class="inline-flex items-center gap-1 rounded-full bg-secondary py-0.5 pr-1 pl-2.5 text-xs font-bold text-secondary-foreground"
                    >
                      {{ title.title }}
                      <button
                          type="button"
                          class="cursor-pointer rounded-full p-0.5 hover:bg-red-800 hover:text-white"
                          :aria-label="`Remove ${title.title}`"
                          :title="`Remove ${title.title}`"
                          @click="removeTitle(title.id)"
                      >
                        <X class="size-3" />
                      </button>
                    </span>
                  </div>
                  <p
                      v-if="form.errors.titles"
                      class="text-sm text-destructive"
                  >
                    {{ form.errors.titles }}
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
            <p
                v-if="props.customer"
                class="text-sm text-muted-foreground"
            >
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
          >{{ props.customer ? 'Save Customer' : 'Create Customer' }}
          </Button>
        </div>
      </form>
    </Card>
  </AppLayout>
</template>

<style scoped>

</style>
