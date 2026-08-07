<!--
  NoteItem
  --------
  Renders a single note card: title, type badge, body, and a footer with the
  created/updated attribution and edit/remove actions.

  Pure presentational component — it emits `edit` and `remove` (with the note)
  and lets the parent list handle the actual mutations.
-->
<script setup>
import EditRemoveActions from "@/Components/EditRemoveActions.vue";
import CreatedBy from "@/Components/CreatedBy.vue";
import StatusBadge from "@/Components/StatusBadge.vue";

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
        <StatusBadge :status="note.type" />
      </div>

    <!-- Note body is sanitized HTML from the server; safe to render. -->
    <div
        class="note-content mt-2 text-sm"
        v-html="note.content"
    />
    <hr class="my-2" />
    <!-- Footer: attribution on the left, edit/remove actions on the right. -->
    <div class="flex flex-col sm:flex-row p-4 justify-between items-center bg-muted">
      <CreatedBy
          :date="note.created_at"
          :user="note.creation_activity?.causer"
          :updated_date="note.updated_at"
          :updater="note.update_activity?.causer"
      />
      <!-- Action buttons: bubble intent up to the parent via emits. -->
      <EditRemoveActions @edit="$emit('edit', note)" @remove="$emit('remove', note)" />
    </div>

  </div>
</template>
