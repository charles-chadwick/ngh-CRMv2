<!--
  MessagesSection
  ---------------
  The customer-page conversation, presented exactly like the notes section: an
  inline add/edit form plus a list of message cards. Mutations route through the
  customer's conversation endpoints; the parent owns the add-form visibility.
-->
<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import MessageItem from "@/Components/Topics/MessageItem.vue";
import MessageForm from "@/Components/Topics/MessageForm.vue";

const props = defineProps({
  customer_id: { type: [Number, String], required: true },
  messages: { type: Object, required: true },
})

const showForm = defineModel('showForm', { type: Boolean, default: false })
const editingId = ref(null)

const onSaved = () => {
  showForm.value = false
  editingId.value = null
}

const startEdit = (message) => {
  showForm.value = false
  editingId.value = message.id
}

const removeMessage = (message) => {
  router.delete(route('messages.destroy', message.id), { preserveScroll: true })
}
</script>

<template>
  <section>
    <MessageForm
        v-if="showForm"
        :customer_id="customer_id"
        class="mb-4"
        @saved="onSaved"
        @cancel="showForm = false"
    />

    <div
        v-if="messages.data.length"
        class="space-y-3"
    >
      <template
          v-for="message in messages.data"
          :key="message.id"
      >
        <MessageForm
            v-if="editingId === message.id"
            :customer_id="customer_id"
            :message="message"
            @saved="onSaved"
            @cancel="editingId = null"
        />
        <MessageItem
            v-else
            :message="message"
            editable
            @edit="startEdit"
            @remove="removeMessage"
        />
      </template>
    </div>
    <p
        v-else
        class="text-sm text-muted-foreground"
    >
      No messages yet.
    </p>
  </section>
</template>
