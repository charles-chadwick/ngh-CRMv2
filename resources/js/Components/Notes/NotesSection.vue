<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { Button } from "@/Components/UI/button/index.js";
import Pagination from "@/Components/Pagination.vue";
import NoteCard from "@/Components/Notes/NoteCard.vue";
import NoteForm from "@/Components/Notes/NoteForm.vue";

const props = defineProps({
  notable_type: { type: String, required: true },
  notable_id: { type: [Number, String], required: true },
  notes: { type: Object, required: true },
  types: { type: Array, default: () => [] },
})

const showForm = ref(false)
const editingId = ref(null)

const onSaved = () => {
  showForm.value = false
  editingId.value = null
}

const openCreateForm = () => {
  showForm.value = true
  editingId.value = null
}

const startEdit = (note) => {
  showForm.value = false
  editingId.value = note.id
}

const removeNote = (note) => {
  router.delete(route('notes.destroy', note.id), { preserveScroll: true })
}
</script>

<template>
  <section>
    <div class="flex items-center justify-between">
      <h2 class="heading">Notes</h2>
      <Button
          v-if="!showForm"
          variant="outline"
          size="sm"
          @click="openCreateForm"
      >
        Add Note
      </Button>
    </div>

    <NoteForm
        v-if="showForm"
        :notable_type="notable_type"
        :notable_id="notable_id"
        :types="types"
        class="mb-4"
        @saved="onSaved"
        @cancel="showForm = false"
    />

    <div
        v-if="notes.data.length"
        class="space-y-3"
    >
      <template
          v-for="note in notes.data"
          :key="note.id"
      >
        <NoteForm
            v-if="editingId === note.id"
            :notable_type="notable_type"
            :notable_id="notable_id"
            :note="note"
            :types="types"
            @saved="onSaved"
            @cancel="editingId = null"
        />
        <NoteCard
            v-else
            :note="note"
            @edit="startEdit"
            @remove="removeNote"
        />
      </template>
    </div>
    <p
        v-else
        class="text-sm text-muted-foreground"
    >
      No notes yet.
    </p>

    <Pagination :paginator="notes" />
  </section>
</template>
