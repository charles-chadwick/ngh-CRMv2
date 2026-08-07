<!--
  ContactItem
  -----------
  Renders a single contact, mirroring NoteItem's layout: a header with the
  primary marker and type badge, the contact details, and a footer with the
  created/updated attribution plus set-primary / edit / remove actions.

  Pure presentational component — it emits `edit`, `remove`, and `setPrimary`
  (with the contact) and lets the parent list handle the actual mutations.
-->
<script setup>
import { Button } from "@/Components/UI/button/index.js";
import { Star } from "lucide-vue-next";
import EditRemoveActions from "@/Components/EditRemoveActions.vue";
import ContactCard from "@/Components/Contacts/ContactCard.vue";
import CreatedBy from "@/Components/CreatedBy.vue";

// `contact` carries its serialized activity log (creation_activity / update_activity)
// so <CreatedBy> can attribute both who made it and who last edited it.
defineProps({
  contact: { type: Object, required: true },
});

defineEmits(["edit", "remove", "setPrimary"]);
</script>

<template>
  <div>
      <!-- Header: contact type as the title on the left, primary marker on the right. -->
      <div class="flex items-center justify-between gap-2">
        <h3 class="font-bold">{{ contact.type }}</h3>
        <span
            v-if="contact.is_primary"
            class="text-sm font-bold text-primary"
        >
          Primary
        </span>
      </div>

    <ContactCard :contact="contact" />
    <hr class="my-2" />
    <!-- Footer: attribution on the left, action buttons on the right. -->
    <div class="flex flex-col sm:flex-row p-4 justify-between items-center bg-muted">
      <CreatedBy
          :date="contact.created_at"
          :user="contact.creation_activity?.causer"
          :updated_date="contact.updated_at"
          :updater="contact.update_activity?.causer"
      />
      <!-- Action buttons: bubble intent up to the parent via emits. -->
      <EditRemoveActions @edit="$emit('edit', contact)" @remove="$emit('remove', contact)">
        <template #prepend>
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
        </template>
      </EditRemoveActions>
    </div>

  </div>
</template>
