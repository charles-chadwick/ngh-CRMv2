<!--suppress JSUnresolvedReference -->
<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import CreatedBy from '@/Components/CreatedBy.vue'
import NotesSection from '@/Components/Notes/NotesSection.vue'
import TasksSection from '@/Components/Tasks/TasksSection.vue'
import AddNoteButton from '@/Components/Notes/AddNoteButton.vue'
import AddTaskButton from '@/Components/Tasks/AddTaskButton.vue'
import { Button } from '@/Components/UI/button/index.js'
import { Card } from '@/Components/UI/card/index.js'
import Pagination from '@/Components/Pagination.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import { Pencil, Trash2 } from 'lucide-vue-next'
import { useConfirm } from '@/composables/useConfirm.js'
import { useDate } from '@/composables/useDate.js'

const props = defineProps({
  lead: Object,
  notes: Object,
  note_types: Array,
  tasks: Object,
  task_types: Array,
  task_statuses: Array,
})

const showNoteForm = ref(false)
const showTaskForm = ref(false)

const confirm = useConfirm()
const { dateTimeString } = useDate()

const destroy = async () => {
  if (await confirm({
    title: 'Delete this lead?',
    message: 'This action cannot be undone.',
    confirmLabel: 'Delete',
    variant: 'stone-destructive',
  })) {
    router.delete(route('leads.destroy', props.lead.id))
  }
}
</script>

<template>
  <AppLayout>
    <Head :title="lead.title" />

    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold">{{ lead.title }}</h1>
        <p class="mt-1 flex items-center gap-2">
          <StatusBadge :status="lead.status" />
          <StatusBadge :status="lead.type" />
        </p>
        <CreatedBy
            :show_avatar="false"
            :date="lead.created_at"
            :user="lead.creation_activity?.causer"
            :updated_date="lead.updated_at"
            :updater="lead.update_activity?.causer"
        />
      </div>
      <div class="flex items-center gap-3">
        <Button
            as-child
            variant="stone"
            size="icon-sm"
            aria-label="Edit"
            title="Edit"
        >
          <Link :href="route('leads.edit', lead.id)"><Pencil /></Link>
        </Button>
        <Button
            aria-label="Delete"
            title="Delete"
            size="icon-sm"
            variant="stone-destructive"
            @click="destroy"
        >
          <Trash2 />
        </Button>
      </div>
    </div>

    <Card class="px-6">
      <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <dt class="text-sm text-muted-foreground">Company</dt>
          <dd>
            <Link
                v-if="lead.company"
                :href="route('companies.show', lead.company.id)"
                class="hover:text-primary"
            >
              {{ lead.company.title }}
            </Link>
            <span v-else>—</span>
          </dd>
        </div>
        <div>
          <dt class="text-sm text-muted-foreground">Status</dt>
          <dd><StatusBadge :status="lead.status" /></dd>
        </div>
        <div>
          <dt class="text-sm text-muted-foreground">Type</dt>
          <dd><StatusBadge :status="lead.type" /></dd>
        </div>
        <div>
          <dt class="text-sm text-muted-foreground">Assigned to</dt>
          <dd>{{ lead.assignee?.full_name || '—' }}</dd>
        </div>
        <div>
          <dt class="text-sm text-muted-foreground">Initiated at</dt>
          <dd>{{ dateTimeString(lead.initiated_at) || '—' }}</dd>
        </div>
      </dl>
    </Card>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <div class="flex items-center justify-between">
          <h2 class="heading">Notes</h2>
          <AddNoteButton
              v-if="!showNoteForm"
              @click="showNoteForm = true"
          />
        </div>
        <Card class="px-6">
          <NotesSection
              v-model:show-form="showNoteForm"
              :notable_id="lead.id"
              :notes="notes"
              :types="note_types"
              notable_type="lead"
          />
        </Card>
        <Pagination :paginator="notes" />
      </div>
      <div>
        <div class="flex items-center justify-between">
          <h2 class="heading">Tasks</h2>
          <AddTaskButton
              v-if="!showTaskForm"
              @click="showTaskForm = true"
          />
        </div>
        <Card class="px-6">
          <TasksSection
              v-model:show-form="showTaskForm"
              subject_type="lead"
              :subject_id="lead.id"
              :tasks="tasks"
              :types="task_types"
              :statuses="task_statuses"
          />
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
