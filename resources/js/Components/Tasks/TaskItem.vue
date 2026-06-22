<script setup>
import { computed } from 'vue'
import { Button } from '@/Components/UI/button/index.js'
import { Pencil, Trash2 } from 'lucide-vue-next'
import StatusBadge from '@/Components/StatusBadge.vue'
import Avatar from '@/Components/Avatar.vue'
import TaskStatusToggle from '@/Components/Tasks/TaskStatusToggle.vue'

const props = defineProps({ task: { type: Object, required: true } })
defineEmits(['edit', 'remove'])

const overdue = computed(() =>
  props.task.status === 'Open' && props.task.due_at && new Date(props.task.due_at) < new Date(),
)
const due_label = computed(() => {
  if (!props.task.due_at) { return 'No due date' }
  const d = new Date(props.task.due_at)
  return props.task.all_day ? d.toLocaleDateString() : d.toLocaleString()
})
</script>

<template>
  <div class="rounded-md border border-border p-3">
    <div class="flex items-center justify-between gap-2">
      <h3 class="font-bold" :class="{ 'text-muted-foreground line-through': task.status !== 'Open' }">{{ task.title }}</h3>
      <StatusBadge :status="task.type" />
    </div>
    <p v-if="task.description" class="mt-1 text-sm text-muted-foreground">{{ task.description }}</p>
    <div class="mt-2 flex flex-wrap items-center gap-3 text-sm">
      <span :class="overdue ? 'font-semibold text-destructive' : 'text-muted-foreground'">{{ due_label }}</span>
      <span v-if="task.assignee" class="flex items-center gap-1">
        <Avatar :name="task.assignee.full_name" :src="task.assignee.avatar_url" size="xs" />
        {{ task.assignee.full_name }}
      </span>
      <TaskStatusToggle :task="task" />
    </div>
    <div class="mt-2 flex justify-end gap-2">
      <Button size="icon-sm" title="Edit" aria-label="Edit" @click="$emit('edit', task)"><Pencil /></Button>
      <Button size="icon-sm" variant="ghost" class="text-destructive hover:text-destructive" title="Remove" aria-label="Remove" @click="$emit('remove', task)"><Trash2 /></Button>
    </div>
  </div>
</template>
