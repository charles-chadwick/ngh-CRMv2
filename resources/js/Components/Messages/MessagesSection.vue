<!--
  MessagesSection
  ---------------
  The customer-page conversation, presented exactly like the notes section: an
  inline add/edit form plus a list of message cards. Mutations route through the
  polymorphic message endpoints; the parent owns the add-form visibility. New
  messages from other actors arrive over Reverb and trigger a partial reload, so
  the server stays the source of truth.
-->
<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import MessageItem from "@/Components/Messages/MessageItem.vue";
import MessageForm from "@/Components/Messages/MessageForm.vue";
import { useConfirm } from '@/composables/useConfirm.js'

const props = defineProps({
  messageable_type: { type: String, required: true },
  messageable_id: { type: [Number, String], required: true },
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

const confirm = useConfirm()

const removeMessage = async (message) => {
  if (await confirm({
    title: 'Delete this message?',
    message: 'This action cannot be undone.',
    confirmLabel: 'Delete',
    variant: 'stone-destructive',
  })) {
    router.delete(route('messages.destroy', message.id), { preserveScroll: true })
  }
}

// Real-time: when another actor posts on this customer's channel, refetch just
// the messages prop. `toOthers()` means the poster never receives this.
const channel = `customers.${props.messageable_id}`

onMounted(() => {
  if (typeof window === 'undefined' || !window.Echo) {
    return
  }
  window.Echo.private(channel).listen('.MessagePosted', () => {
    router.reload({ only: ['messages'], preserveScroll: true })
  })
})

onUnmounted(() => {
  if (typeof window !== 'undefined' && window.Echo) {
    window.Echo.leave(channel)
  }
})
</script>

<template>
  <section>
    <MessageForm
        v-if="showForm"
        :messageable_type="messageable_type"
        :messageable_id="messageable_id"
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
            :messageable_type="messageable_type"
            :messageable_id="messageable_id"
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
