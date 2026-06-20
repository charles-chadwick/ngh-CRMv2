<!--suppress JSUnresolvedReference -->
<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import CreatedBy from '@/Components/CreatedBy.vue'
import NotesSection from '@/Components/Notes/NotesSection.vue'
import AddNoteButton from '@/Components/Notes/AddNoteButton.vue'
import { Button } from '@/Components/UI/button/index.js'
import { Card } from '@/Components/UI/card/index.js'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  lead: Object,
  notes: Object,
  note_types: Array,
})

const showNoteForm = ref(false)

const destroy = () => {
  if (confirm('Delete this lead?')) {
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
        <p class="text-muted-foreground">{{ lead.status }} / {{ lead.type }}</p>
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
            variant="outline"
        >
          <Link :href="route('leads.edit', lead.id)">Edit</Link>
        </Button>
        <Button
            variant="destructive"
            @click="destroy"
        >
          Delete
        </Button>
      </div>
    </div>

    <Card class="px-6">
      <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <dt class="text-sm text-muted-foreground">Status</dt>
          <dd>{{ lead.status }}</dd>
        </div>
        <div>
          <dt class="text-sm text-muted-foreground">Type</dt>
          <dd>{{ lead.type }}</dd>
        </div>
        <div>
          <dt class="text-sm text-muted-foreground">Assigned to</dt>
          <dd>{{ lead.assignee?.full_name || '—' }}</dd>
        </div>
        <div>
          <dt class="text-sm text-muted-foreground">Initiated at</dt>
          <dd>{{ lead.initiated_at || '—' }}</dd>
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
    </div>
  </AppLayout>
</template>
