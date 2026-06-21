<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import ContactItem from "@/Components/Contacts/ContactItem.vue";
import ContactForm from "@/Components/Contacts/ContactForm.vue";
import { useConfirm } from "@/composables/useConfirm.js";

const props = defineProps({
  contactable_type: { type: String, required: true },
  contactable_id: { type: [Number, String], required: true },
  contacts: { type: Object, required: true },
  types: { type: Array, default: () => [] },
});

const showForm = defineModel("showForm", { type: Boolean, default: false });
const editingId = ref(null);

const onSaved = () => {
  showForm.value = false;
  editingId.value = null;
};

const startEdit = (contact) => {
  showForm.value = false;
  editingId.value = contact.id;
};

const confirm = useConfirm();

const removeContact = async (contact) => {
  if (await confirm({
    title: "Delete this contact?",
    message: "This action cannot be undone.",
    confirmLabel: "Delete",
    variant: "destructive",
  })) {
    router.delete(route("contacts.destroy", contact.id), { preserveScroll: true });
  }
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
  <section>
    <ContactForm
        v-if="showForm"
        :contactable_type="contactable_type"
        :contactable_id="contactable_id"
        :types="types"
        class="mb-4"
        @saved="onSaved"
        @cancel="showForm = false"
    />

    <div
        v-if="contacts.data.length"
        class="space-y-3"
    >
      <template
          v-for="contact in contacts.data"
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
        v-else
        class="text-sm text-muted-foreground"
    >
      No contacts yet.
    </p>
  </section>
</template>
