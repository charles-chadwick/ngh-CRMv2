<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Input from '@/Components/UI/input/Input.vue'
import { Button } from '@/Components/UI/button/index.js'
import { Label } from '@/Components/UI/label/index.js'
import { Card } from '@/Components/UI/card/index.js'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/UI/select/index.js'

const props = defineProps({
  lead: { type: Object, default: null },
  users: { type: Array, default: () => [] },
  statuses: { type: Array, default: () => [] },
  types: { type: Array, default: () => [] },
})

const form = useForm({
  title: props.lead?.title || '',
  type: props.lead?.type || '',
  status: props.lead?.status || '',
  assigned_to: props.lead?.assigned_to || null,
  initiated_at: props.lead?.initiated_at?.slice(0, 10) || '',
})

const submit = () => {
  if (props.lead) {
    form.put(route('leads.update', props.lead.id))
  } else {
    form.post(route('leads.store'))
  }
}
</script>

<template>
  <AppLayout>
    <h1 class="text-2xl font-bold my-6">{{ lead ? 'Edit lead' : 'New lead' }}</h1>
    <Card class="px-6">
      <form @submit.prevent="submit">
        <div class="w-full space-y-6 p-6">
          <div class="space-y-2">
            <Label for="title">Title</Label>
            <Input
                id="title"
                type="text"
                placeholder="Lead title"
                v-model="form.title"
            />
            <p
                v-if="form.errors.title"
                class="text-sm text-destructive"
            >
              {{ form.errors.title }}
            </p>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-2">
              <Label for="type">Type</Label>
              <Select v-model="form.type">
                <SelectTrigger
                    id="type"
                    class="w-full"
                >
                  <SelectValue placeholder="Select a type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                      v-for="type in types"
                      :key="type.value"
                      :value="type.value"
                  >
                    {{ type.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p
                  v-if="form.errors.type"
                  class="text-sm text-destructive"
              >
                {{ form.errors.type }}
              </p>
            </div>

            <div class="space-y-2">
              <Label for="status">Status</Label>
              <Select v-model="form.status">
                <SelectTrigger
                    id="status"
                    class="w-full"
                >
                  <SelectValue placeholder="Select a status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                      v-for="status in statuses"
                      :key="status.value"
                      :value="status.value"
                  >
                    {{ status.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p
                  v-if="form.errors.status"
                  class="text-sm text-destructive"
              >
                {{ form.errors.status }}
              </p>
            </div>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-2">
              <Label for="assigned_to">Assigned to</Label>
              <Select v-model="form.assigned_to">
                <SelectTrigger
                    id="assigned_to"
                    class="w-full"
                >
                  <SelectValue placeholder="Unassigned" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                      v-for="user in users"
                      :key="user.id"
                      :value="user.id"
                  >
                    {{ user.full_name }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p
                  v-if="form.errors.assigned_to"
                  class="text-sm text-destructive"
              >
                {{ form.errors.assigned_to }}
              </p>
            </div>

            <div class="space-y-2">
              <Label for="initiated_at">Initiated at</Label>
              <Input
                  id="initiated_at"
                  type="date"
                  v-model="form.initiated_at"
              />
              <p
                  v-if="form.errors.initiated_at"
                  class="text-sm text-destructive"
              >
                {{ form.errors.initiated_at }}
              </p>
            </div>
          </div>
        </div>
        <div class="flex items-center justify-end gap-3 border-t p-6">
          <Button
              type="submit"
              :disabled="form.processing"
          >
            Save Lead
          </Button>
        </div>
      </form>
    </Card>
  </AppLayout>
</template>
