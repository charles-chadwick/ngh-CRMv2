<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import NoteItem from "@/Components/Notes/NoteItem.vue";
import NoteForm from "@/Components/Notes/NoteForm.vue";
import { useConfirm } from '@/composables/useConfirm.js'

const props = defineProps({
  notable_type: { type: String, required: true },
  notable_id: { type: [Number, String], required: true },
  notes: { type: Object, required: true },
  types: { type: Array, default: () => [] },
})

const showForm = defineModel('showForm', { type: Boolean, default: false })
const editingId = ref(null)

const onSaved = () => {
  showForm.value = false
  editingId.value = null
}

const startEdit = (note) => {
  showForm.value = false
  editingId.value = note.id
}

const confirm = useConfirm()

const removeNote = async (note) => {
  if (await confirm({
    title: 'Delete this note?',
    message: 'This action cannot be undone.',
    confirmLabel: 'Delete',
    variant: 'stone-destructive',
  })) {
    router.delete(route('notes.destroy', note.id), { preserveScroll: true })
  }
}
</script>

<template>
  <section>
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
        <NoteItem
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
  </section>
</template>
