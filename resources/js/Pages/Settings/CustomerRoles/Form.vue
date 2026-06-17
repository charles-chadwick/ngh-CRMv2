<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Head, Link, useForm } from '@inertiajs/vue3'
import Input from "@/Components/UI/input/Input.vue";
import { Button } from "@/Components/UI/button/index.js";
import { Label } from "@/Components/UI/label/index.js";
import { Card } from "@/Components/UI/card/index.js";

const props = defineProps({
  customer_role: { type: Object, default: null },
})

const is_edit = !!props.customer_role

const form = useForm({
  name: props.customer_role?.name || '',
})

const submit = () => {
  if (is_edit) {
    form.put(route('settings.customer-roles.update', props.customer_role.id))
  } else {
    form.post(route('settings.customer-roles.store'))
  }
}
</script>

<template>
  <AppLayout>
    <Head :title="is_edit ? 'Edit Customer Role' : 'New Customer Role'" />

    <div class="my-6">
      <Link :href="route('settings.customer-roles.index')" class="text-sm text-muted-foreground hover:text-primary">
        &larr; Customer Roles
      </Link>
      <h1 class="text-2xl font-bold">{{ is_edit ? 'Edit Customer Role' : 'New Customer Role' }}</h1>
    </div>

    <Card class="px-6">
      <form @submit.prevent="submit">
        <div class="space-y-6 p-6">
          <div class="space-y-2 max-w-md">
            <Label for="name">Name</Label>
            <Input
                id="name"
                type="text"
                placeholder="Role name"
                v-model="form.name"
            />
            <p v-if="form.errors.name" class="text-sm text-destructive">
              {{ form.errors.name }}
            </p>
          </div>
        </div>
        <div class="flex items-center justify-end gap-3 border-t p-6">
          <Button as-child variant="outline">
            <Link :href="route('settings.customer-roles.index')">Cancel</Link>
          </Button>
          <Button type="submit" :disabled="form.processing">Save Role</Button>
        </div>
      </form>
    </Card>
  </AppLayout>
</template>
