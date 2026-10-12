<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import Input from "@/Components/UI/input/Input.vue";
import { Button } from "@/Components/UI/button/index.js";
import { Label } from "@/Components/UI/label/index.js";
import AvatarUpload from "@/Components/AvatarUpload.vue";
import { useForm } from '@inertiajs/vue3'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/Components/UI/select/index.js";
import { Card } from "@/Components/UI/card/index.js";

const props = defineProps({
  company: Object,
  types: { type: Array, default: () => [] },
});

const form = useForm({
  title: props.company?.title || '',
  company_type_id: props.company?.company_type_id || null,
  description: props.company?.description || '',
});

const submit = () => {
  if (props.company) {
    form.put(route('companies.update', props.company.id));
  } else {
    form.post(route('companies.store'));
  }
};
</script>

<template>
  <AppLayout>
    <div class="flex items-center justify-between mb-6">
      <div v-if="company">
        <h1 class="text-2xl font-bold">{{ company.title }}</h1>
        <p class="text-muted-foreground">{{ company.type?.name }}</p>
      </div>
      <h1
          v-else
          class="text-2xl font-bold"
      >New Company</h1>
    </div>
    <Card class="px-6">
      <form @submit.prevent="submit">
        <div class="flex flex-row justify-between gap-4">
          <!-- Body -->
          <div class="w-full space-y-6 p-6">

            <div class="grid gap-4 sm:grid-cols-2">
              <div class="space-y-2">
                <Label for="title">Title</Label>
                <Input
                    id="title"
                    type="text"
                    placeholder="Company name"
                    v-model="form.title"
                />
                <p
                    v-if="form.errors.title"
                    class="text-sm text-destructive"
                >
                  {{ form.errors.title }}
                </p>
              </div>

              <div class="space-y-2">
                <Label for="company_type_id">Type</Label>
                <Select v-model="form.company_type_id">
                  <SelectTrigger
                      id="company_type_id"
                      class="w-full"
                  >
                    <SelectValue placeholder="Select a type" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem
                        v-for="type in props.types"
                        :key="type.id"
                        :value="type.id"
                    >
                      {{ type.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <p
                    v-if="form.errors.company_type_id"
                    class="text-sm text-destructive"
                >
                  {{ form.errors.company_type_id }}
                </p>
              </div>
            </div>

            <div class="space-y-2">
              <Label for="description">Description</Label>
              <textarea
                  id="description"
                  rows="4"
                  placeholder="Company description"
                  v-model="form.description"
                  class="placeholder:text-muted-foreground dark:bg-input/30 border-input w-full min-w-0 rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-3 md:text-sm"
              />
              <p
                  v-if="form.errors.description"
                  class="text-sm text-destructive"
              >
                {{ form.errors.description }}
              </p>
            </div>
          </div>

          <!-- Avatar -->
          <AvatarUpload
              v-if="props.company"
              model_type="company"
              :model_id="props.company.id"
              :current_image="props.company.avatar_url"
              :name="props.company.title"
              label="Logo"
          />
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-end gap-3 border-t p-6">
          <Button
              type="submit"
              :disabled="form.processing"
          >Save Company
          </Button>
        </div>
      </form>
    </Card>
  </AppLayout>
</template>

<style scoped>

</style>
