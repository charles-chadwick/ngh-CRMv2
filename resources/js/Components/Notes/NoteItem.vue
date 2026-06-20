<!--
  NoteItem
  --------
  Renders a single note card: title, type badge, body, and a footer with the
  created/updated attribution and edit/remove actions.

  Pure presentational component — it emits `edit` and `remove` (with the note)
  and lets the parent list handle the actual mutations.
-->
<script setup>
import { Button } from "@/Components/UI/button/index.js";
import { Pencil, Trash2 } from "lucide-vue-next"
import CreatedBy from "@/Components/CreatedBy.vue";

// `note` carries its serialized activity log (creation_activity / update_activity)
// so <CreatedBy> can attribute both who made it and who last edited it.
defineProps ( {
  note: { type: Object, required: true },
} )

defineEmits ( [ 'edit', 'remove' ] )
</script>

<template>
  <div>
      <!-- Header: note title on the left, optional type badge on the right. -->
      <div class="flex items-center justify-between gap-2">
        <h3 class="font-bold">{{ note.title }}</h3>
        <div
            v-if="note.type"
            class="rounded-full bg-secondary px-2.5 py-0.5 text-xs font-bold text-secondary-foreground"
        >
          {{ note.type }}
        </div>
      </div>

    <!-- Note body is sanitized HTML from the server; safe to render. -->
    <div
        class="note-content mt-2 text-sm"
        v-html="note.content"
    />
    <hr class="my-2" />
    <!-- Footer: attribution on the left, edit/remove actions on the right. -->
    <div class="flex flex-col sm:flex-row p-4 justify-between items-center bg-stone-100">
      <CreatedBy
          :date="note.created_at"
          :user="note.creation_activity?.causer"
          :updated_date="note.updated_at"
          :updater="note.update_activity?.causer"
      />
      <!-- Action buttons: bubble intent up to the parent via emits. -->
      <div>
        <Button
            aria-label="Edit"
            size="icon-sm"
            title="Edit"
            variant="ghost"
            @click="$emit('edit', note)"
        >
          <Pencil />
        </Button>
        <Button
            aria-label="Remove"
            class="text-destructive hover:text-destructive"
            size="icon-sm"
            title="Remove"
            variant="ghost"
            @click="$emit('remove', note)"
        >
          <Trash2 />
        </Button>
      </div>
    </div>

  </div>
</template>
