<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/Components/UI/button/index.js'
import { Card } from '@/Components/UI/card/index.js'
import TaskItem from '@/Components/Tasks/TaskItem.vue'
import TaskForm from '@/Components/Tasks/TaskForm.vue'
import TaskCalendar from '@/Components/Tasks/TaskCalendar.vue'
import { useConfirm } from '@/composables/useConfirm.js'

const props = defineProps({
  overdue: { type: Array, default: () => [] },
  due_today: { type: Array, default: () => [] },
  upcoming: { type: Array, default: () => [] },
  calendar: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
  types: { type: Array, default: () => [] },
  statuses: { type: Array, default: () => [] },
})

const view = ref('list')
const showForm = ref(false)
const editing = ref(null)
const confirm = useConfirm()

const allTasks = () => [...props.overdue, ...props.due_today, ...props.upcoming]

const reschedule = ({ id, due_at }) => {
  const task = allTasks().find((t) => String(t.id) === String(id))
  router.put(route('tasks.update', id), {
    type: task?.type || 'To-Do',
    title: task?.title || 'Task',
    due_at,
  }, { preserveScroll: true })
}

const remove = async (task) => {
  if (await confirm({ title: 'Delete task?', message: `Delete "${task.title}"?`, variant: 'destructive' })) {
    router.delete(route('tasks.destroy', task.id), { preserveScroll: true })
  }
}

const onRangeChange = ({ start, end }) => {
  router.reload({ data: { start, end }, only: ['calendar'] })
}

const onSaved = () => { showForm.value = false; editing.value = null }
const startEdit = (task) => { editing.value = task; showForm.value = false; view.value = 'list' }
</script>

<template>
  <AppLayout>
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <h1 class="heading">My Activities</h1>
        <div class="flex gap-2">
          <Button variant="secondary" size="sm" @click="view = view === 'list' ? 'calendar' : 'list'">
            {{ view === 'list' ? 'Calendar' : 'List' }}
          </Button>
          <Button size="sm" @click="showForm = !showForm; editing = null">New Task</Button>
        </div>
      </div>

      <TaskForm v-if="showForm" :types="types" @saved="onSaved" @cancel="showForm = false" />

      <div v-if="view === 'list'" class="space-y-6">
        <section v-if="overdue.length">
          <h2 class="mb-2 font-bold text-destructive">Overdue</h2>
          <div class="space-y-2">
            <template v-for="t in overdue" :key="t.id">
              <TaskForm v-if="editing && editing.id === t.id" :task="t" :types="types" @saved="onSaved" @cancel="editing = null" />
              <TaskItem v-else :task="t" @remove="remove" @edit="startEdit" />
            </template>
          </div>
        </section>

        <section>
          <h2 class="mb-2 font-bold">Due Today</h2>
          <div class="space-y-2">
            <template v-for="t in due_today" :key="t.id">
              <TaskForm v-if="editing && editing.id === t.id" :task="t" :types="types" @saved="onSaved" @cancel="editing = null" />
              <TaskItem v-else :task="t" @remove="remove" @edit="startEdit" />
            </template>
          </div>
          <p v-if="!due_today.length" class="text-sm text-muted-foreground">Nothing due today.</p>
        </section>

        <section>
          <h2 class="mb-2 font-bold">Upcoming</h2>
          <div class="space-y-2">
            <template v-for="t in upcoming" :key="t.id">
              <TaskForm v-if="editing && editing.id === t.id" :task="t" :types="types" @saved="onSaved" @cancel="editing = null" />
              <TaskItem v-else :task="t" @remove="remove" @edit="startEdit" />
            </template>
          </div>
          <p v-if="!upcoming.length" class="text-sm text-muted-foreground">Nothing upcoming.</p>
        </section>
      </div>

      <Card v-else class="p-4">
        <TaskCalendar :events="calendar" @reschedule="reschedule" @range-change="onRangeChange" />
      </Card>
    </div>
  </AppLayout>
</template>
