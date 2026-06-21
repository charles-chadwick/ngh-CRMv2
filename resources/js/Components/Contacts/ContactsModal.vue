<script setup>
import { computed, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/Components/UI/dialog/index.js";
import { Button } from "@/Components/UI/button/index.js";
import { ChevronLeft, ChevronRight, Plus } from "lucide-vue-next";
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

const perPage = 3;
const currentPage = ref(1);

const totalPages = computed(() =>
  Math.max(1, Math.ceil(props.contacts.length / perPage)),
);

const paginatedContacts = computed(() => {
  const start = (currentPage.value - 1) * perPage;
  return props.contacts.slice(start, start + perPage);
});

// Keep the current page in range when contacts change (e.g. after delete).
watch(totalPages, (pages) => {
  if (currentPage.value > pages) {
    currentPage.value = pages;
  }
});

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
            v-for="contact in paginatedContacts"
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

        <div
            v-if="totalPages > 1"
            class="flex items-center justify-end gap-2 pt-1"
        >
          <Button
              variant="outline"
              size="sm"
              :disabled="currentPage === 1"
              @click="currentPage--"
          >
            <ChevronLeft class="size-4" />
          </Button>
          <span class="text-sm text-muted-foreground">
            Page {{ currentPage }} of {{ totalPages }}
          </span>
          <Button
              variant="outline"
              size="sm"
              :disabled="currentPage === totalPages"
              @click="currentPage++"
          >
            <ChevronRight class="size-4" />
          </Button>
        </div>
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
