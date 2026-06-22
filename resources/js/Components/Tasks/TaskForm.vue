<script setup>
import { useForm } from '@inertiajs/vue3'
import { Label } from '@/Components/UI/label/index.js'
import { Input } from '@/Components/UI/input/index.js'
import { Button } from '@/Components/UI/button/index.js'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/UI/select/index.js'
import { Card, CardContent } from '@/Components/UI/card/index.js'
import AssigneePicker from '@/Components/Tasks/AssigneePicker.vue'
import SubjectPicker from '@/Components/Tasks/SubjectPicker.vue'

const props = defineProps({
  subject_type: { type: String, default: null },
  subject_id: { type: [Number, String], default: null },
  task: { type: Object, default: null },
  types: { type: Array, default: () => [] },
})
const emit = defineEmits(['saved', 'cancel'])

const form = useForm({
  subject_type: props.subject_type,
  subject_id: props.subject_id,
  assignee_type: props.task?.assignee_type_key || null,
  assignee_id: props.task?.assignee?.id || null,
  type: props.task?.type || props.types[0]?.value || 'To-Do',
  title: props.task?.title || '',
  description: props.task?.description || '',
  due_at: props.task?.due_at ? props.task.due_at.slice(0, 16) : '',
  all_day: props.task?.all_day || false,
  remind_at: props.task?.remind_at ? props.task.remind_at.slice(0, 16) : '',
})

const submit = () => {
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      if (!props.task) { form.reset('title', 'description', 'due_at', 'remind_at') }
      emit('saved')
    },
  }
  if (props.task) {
    form.put(route('tasks.update', props.task.id), options)
  } else {
    form.post(route('tasks.store'), options)
  }
}
</script>

<template>
  <Card>
    <CardContent>
      <form class="space-y-3" @submit.prevent="submit">
        <div class="grid gap-3 sm:grid-cols-3">
          <div class="space-y-2 sm:col-span-2">
            <Label for="task_title">Title</Label>
            <Input id="task_title" v-model="form.title" type="text" placeholder="Task title" />
            <p v-if="form.errors.title" class="text-sm text-destructive">{{ form.errors.title }}</p>
          </div>
          <div class="space-y-2">
            <Label for="task_type">Type</Label>
            <Select v-model="form.type">
              <SelectTrigger id="task_type" class="w-full"><SelectValue placeholder="Type" /></SelectTrigger>
              <SelectContent>
                <SelectItem v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>

        <div v-if="!subject_type" class="space-y-2">
          <Label>About</Label>
          <SubjectPicker
              @update:model-type="form.subject_type = $event"
              @update:model-id="form.subject_id = $event"
          />
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
          <div class="space-y-2">
            <Label>Assignee</Label>
            <AssigneePicker
                :initial-label="task?.assignee?.full_name || ''"
                @update:model-type="form.assignee_type = $event"
                @update:model-id="form.assignee_id = $event"
            />
          </div>
          <div class="space-y-2">
            <Label for="task_due">Due</Label>
            <Input id="task_due" v-model="form.due_at" :type="form.all_day ? 'date' : 'datetime-local'" />
            <label class="flex items-center gap-2 text-sm">
              <input v-model="form.all_day" type="checkbox"> All day
            </label>
          </div>
        </div>

        <div class="space-y-2">
          <Label for="task_remind">Remind at (optional)</Label>
          <Input id="task_remind" v-model="form.remind_at" type="datetime-local" />
        </div>

        <div class="flex justify-end gap-3">
          <Button type="button" variant="ghost" size="sm" @click="emit('cancel')">Cancel</Button>
          <Button type="submit" size="sm" :disabled="form.processing">{{ task ? 'Update Task' : 'Save Task' }}</Button>
        </div>
      </form>
    </CardContent>
  </Card>
</template>
