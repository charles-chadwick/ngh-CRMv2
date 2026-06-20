<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/Components/UI/dialog/index.js";
import { Button } from "@/Components/UI/button/index.js";
import { Plus } from "lucide-vue-next";
import ContactItem from "@/Components/Contacts/ContactItem.vue";
import ContactForm from "@/Components/Contacts/ContactForm.vue";

const props = defineProps({
  contactable_type: { type: String, required: true },
  contactable_id: { type: [Number, String], required: true },
  contacts: { type: Array, default: () => [] },
  types: { type: Array, default: () => [] },
});

const open = defineModel("open", { type: Boolean, default: false });

const showForm = ref(false);
const editingId = ref(null);

const onSaved = () => {
  showForm.value = false;
  editingId.value = null;
};

const startEdit = (contact) => {
  showForm.value = false;
  editingId.value = contact.id;
};

const removeContact = (contact) => {
  router.delete(route("contacts.destroy", contact.id), { preserveScroll: true });
};

const setPrimary = (contact) => {
  router.patch(
    route("contacts.set-primary", contact.id),
    {},
    { preserveScroll: true },
  );
};
</script>

<template>
  <Dialog v-model:open="open">
    <DialogContent class="max-w-2xl">
      <DialogHeader>
        <DialogTitle>Contacts</DialogTitle>
      </DialogHeader>

      <div class="flex justify-end">
        <Button
            v-if="!showForm"
            variant="default"
            size="sm"
            @click="showForm = true; editingId = null"
        >
          <Plus class="size-4" />
          Add contact
        </Button>
      </div>

      <ContactForm
          v-if="showForm"
          :contactable_type="contactable_type"
          :contactable_id="contactable_id"
          :types="types"
          @saved="onSaved"
          @cancel="showForm = false"
      />

      <div
          v-if="contacts.length"
          class="space-y-3"
      >
        <template
            v-for="contact in contacts"
            :key="contact.id"
        >
          <ContactForm
              v-if="editingId === contact.id"
              :contactable_type="contactable_type"
              :contactable_id="contactable_id"
              :contact="contact"
              :types="types"
              @saved="onSaved"
              @cancel="editingId = null"
          />
          <ContactItem
              v-else
              :contact="contact"
              @edit="startEdit"
              @remove="removeContact"
              @set-primary="setPrimary"
          />
        </template>
      </div>
      <p
          v-else-if="!showForm"
          class="text-sm text-muted-foreground"
      >
        No contacts yet.
      </p>
    </DialogContent>
  </Dialog>
</template>
