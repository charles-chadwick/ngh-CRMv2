<script setup>
import { computed, ref } from "vue";
import { Card } from "@/Components/UI/card/index.js";
import { Button } from "@/Components/UI/button/index.js";
import ContactCard from "@/Components/Contacts/ContactCard.vue";
import ContactsModal from "@/Components/Contacts/ContactsModal.vue";

const props = defineProps({
  contactable_type: { type: String, required: true },
  contactable_id: { type: [Number, String], required: true },
  contacts: { type: Array, default: () => [] },
  types: { type: Array, default: () => [] },
});

const modalOpen = ref(false);

const primary = computed(
  () => props.contacts.find((contact) => contact.is_primary) || props.contacts[0] || null,
);

</script>

<template>
  <div>
    <div class="flex items-center justify-between">
      <h2 class="heading">Contacts</h2>
    </div>
    <Card class="px-6">
      <template v-if="primary">
        <ContactCard :contact="primary" />
        <div class="mt-4 flex justify-end">
          <Button
              variant="outline"
              size="sm"
              @click="modalOpen = true"
          >
            View all contacts ({{ contacts.length }})
          </Button>
        </div>
      </template>
      <template v-else>
        <p class="text-sm text-muted-foreground">No contacts yet.</p>
        <div class="mt-4 flex justify-end">
          <Button
              variant="outline"
              size="sm"
              @click="modalOpen = true"
          >
            Add contact
          </Button>
        </div>
      </template>
    </Card>

    <ContactsModal
        v-model:open="modalOpen"
        :contactable_type="contactable_type"
        :contactable_id="contactable_id"
        :contacts="contacts"
        :types="types"
    />
  </div>
</template>
