<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import Pagination from '@/Components/Pagination.vue'
import TaskItem from '@/Components/Tasks/TaskItem.vue'
import TaskForm from '@/Components/Tasks/TaskForm.vue'
import { useConfirm } from '@/composables/useConfirm.js'

const props = defineProps({
  subject_type: { type: String, required: true },
  subject_id: { type: [Number, String], required: true },
  tasks: { type: Object, required: true },
  types: { type: Array, default: () => [] },
  statuses: { type: Array, default: () => [] },
})

const showForm = defineModel('showForm', { type: Boolean, default: false })
const editing = ref(null)
const confirm = useConfirm()

const onSaved = () => { showForm.value = false; editing.value = null }
const startEdit = (task) => { editing.value = task; showForm.value = false }

const remove = async (task) => {
  if (await confirm({ title: 'Delete task?', message: `Delete "${task.title}"?`, variant: 'stone-destructive' })) {
    router.delete(route('tasks.destroy', task.id), { preserveScroll: true })
  }
}
</script>

<template>
  <section class="space-y-3">
    <TaskForm
        v-if="showForm"
        :subject_type="subject_type"
        :subject_id="subject_id"
        :types="types"
        @saved="onSaved"
        @cancel="showForm = false"
    />

    <div class="space-y-2">
      <template v-for="task in tasks.data" :key="task.id">
        <TaskForm
            v-if="editing && editing.id === task.id"
            :subject_type="subject_type"
            :subject_id="subject_id"
            :task="task"
            :types="types"
            @saved="onSaved"
            @cancel="editing = null"
        />
        <TaskItem v-else :task="task" @edit="startEdit" @remove="remove" />
      </template>
      <p v-if="!tasks.data.length" class="text-sm text-muted-foreground">No tasks yet.</p>
    </div>

    <Pagination :paginator="tasks" />
  </section>
</template>
