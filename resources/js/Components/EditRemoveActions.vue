<!--
  EditRemoveActions
  -----------------
  Shared edit + delete action pair used by the record item cards
  (tasks, notes, contacts, messages) so their buttons stay visually
  identical. Emits `edit` and `remove`; the parent item re-emits them
  with its own record payload. The `prepend` slot holds record-specific
  extra actions (e.g. a contact's set-primary star).
-->
<script setup>
import { Button } from "@/Components/UI/button/index.js";
import { Pencil, Trash2 } from "lucide-vue-next";

defineEmits(["edit", "remove"]);
</script>

<template>
  <div class="flex items-center gap-2">
    <slot name="prepend" />
    <Button
        aria-label="Edit"
        size="icon-sm"
        title="Edit"
        variant="stone"
        @click="$emit('edit')"
    >
      <Pencil />
    </Button>
    <Button
        aria-label="Remove"
        size="icon-sm"
        title="Remove"
        variant="stone-destructive"
        @click="$emit('remove')"
    >
      <Trash2 />
    </Button>
  </div>
</template>
