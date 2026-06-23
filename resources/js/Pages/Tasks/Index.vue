<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/Components/UI/button/index.js'
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/UI/card/index.js'
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
const totalOpen = computed(() => allTasks().length)

const reschedule = ({ id, due_at }) => {
  const task = allTasks().find((t) => String(t.id) === String(id))
  router.put(route('tasks.update', id), {
    type: task?.type || 'To-Do',
    title: task?.title || 'Task',
    due_at,
  }, { preserveScroll: true })
}

const remove = async (task) => {
  if (await confirm({ title: 'Delete task?', message: `Delete "${task.title}"?`, variant: 'stone-destructive' })) {
    router.delete(route('tasks.destroy', task.id), { preserveScroll: true })
  }
}

const onRangeChange = ({ start, end }) => {
  router.reload({ data: { start, end }, only: ['calendar'] })
}

const onSaved = () => { showForm.value = false; editing.value = null }
const startEdit = (task) => { editing.value = task; showForm.value = false; view.value = 'list' }
const toggleForm = () => { showForm.value = !showForm.value; editing.value = null; view.value = 'list' }
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex flex-wrap items-center justify-between gap-3 my-6">
        <div>
          <h1 class="text-2xl font-bold">My Activities</h1>
          <p class="text-sm text-muted-foreground">
            {{ totalOpen }} open {{ totalOpen === 1 ? 'task' : 'tasks' }}
          </p>
        </div>
        <div class="flex items-center gap-2">
          <div class="inline-flex rounded-md border border-border p-0.5">
            <button
                type="button"
                class="cursor-pointer rounded-sm px-3 py-1 text-sm font-semibold transition-colors"
                :class="view === 'list' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground'"
                @click="view = 'list'"
            >
              List
            </button>
            <button
                type="button"
                class="cursor-pointer rounded-sm px-3 py-1 text-sm font-semibold transition-colors"
                :class="view === 'calendar' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground'"
                @click="view = 'calendar'"
            >
              Calendar
            </button>
          </div>
          <Button size="sm" variant="stone" @click="toggleForm">New Task</Button>
        </div>
      </div>

      <Card v-if="showForm">
        <CardContent>
          <TaskForm :types="types" @saved="onSaved" @cancel="showForm = false" />
        </CardContent>
      </Card>

      <div v-if="view === 'list'" class="grid gap-6 lg:grid-cols-2">
        <Card v-if="overdue.length" class="lg:col-span-2">
          <CardHeader class="border-b border-b-destructive [.border-b]:mx-4">
            <CardTitle class="flex items-center gap-2 text-destructive">
              Overdue
              <span class="rounded-full bg-destructive px-2 py-0.5 text-xs font-bold text-white">{{ overdue.length }}</span>
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-2">
            <template v-for="t in overdue" :key="t.id">
              <TaskForm v-if="editing && editing.id === t.id" :task="t" :types="types" @saved="onSaved" @cancel="editing = null" />
              <TaskItem v-else :task="t" @remove="remove" @edit="startEdit" />
            </template>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="border-b border-b-primary [.border-b]:mx-4">
            <CardTitle class="flex items-center gap-2">
              Due Today
              <span class="rounded-full bg-primary px-2 py-0.5 text-xs font-bold text-primary-foreground">{{ due_today.length }}</span>
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-2">
            <template v-for="t in due_today" :key="t.id">
              <TaskForm v-if="editing && editing.id === t.id" :task="t" :types="types" @saved="onSaved" @cancel="editing = null" />
              <TaskItem v-else :task="t" @remove="remove" @edit="startEdit" />
            </template>
            <p v-if="!due_today.length" class="text-sm text-muted-foreground">Nothing due today.</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="border-b border-b-primary [.border-b]:mx-4">
            <CardTitle class="flex items-center gap-2">
              Upcoming
              <span class="rounded-full bg-secondary px-2 py-0.5 text-xs font-bold text-secondary-foreground">{{ upcoming.length }}</span>
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-2">
            <template v-for="t in upcoming" :key="t.id">
              <TaskForm v-if="editing && editing.id === t.id" :task="t" :types="types" @saved="onSaved" @cancel="editing = null" />
              <TaskItem v-else :task="t" @remove="remove" @edit="startEdit" />
            </template>
            <p v-if="!upcoming.length" class="text-sm text-muted-foreground">Nothing upcoming.</p>
          </CardContent>
        </Card>
      </div>

      <Card v-else>
        <CardContent>
          <TaskCalendar :events="calendar" @reschedule="reschedule" @range-change="onRangeChange" />
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
