<!--
  ContactItem
  -----------
  One row in the contacts modal: the ContactCard display plus actions
  (set primary, edit, remove). Bubbles intent to the parent via emits.
-->
<script setup>
import { Button } from "@/Components/UI/button/index.js";
import { Pencil, Star, Trash2 } from "lucide-vue-next";
import ContactCard from "@/Components/Contacts/ContactCard.vue";

const props = defineProps({
  contact: { type: Object, required: true },
});

defineEmits(["edit", "remove", "setPrimary"]);
</script>

<template>
  <div class="rounded-md border p-3">
    <ContactCard :contact="contact" />
    <hr class="my-2" />
    <div class="flex justify-end">
      <Button
          v-if="!contact.is_primary"
          aria-label="Set primary"
          size="icon-sm"
          title="Set primary"
          variant="ghost"
          @click="$emit('setPrimary', contact)"
      >
        <Star />
      </Button>
      <Button
          aria-label="Edit"
          size="icon-sm"
          title="Edit"
          variant="ghost"
          @click="$emit('edit', contact)"
      >
        <Pencil />
      </Button>
      <Button
          aria-label="Remove"
          class="text-destructive hover:text-destructive"
          size="icon-sm"
          title="Remove"
          variant="ghost"
          @click="$emit('remove', contact)"
      >
        <Trash2 />
      </Button>
    </div>
  </div>
</template>
