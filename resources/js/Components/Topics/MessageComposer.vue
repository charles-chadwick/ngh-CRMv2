<!--
  MessageComposer
  ---------------
  Rich-text composer pinned to the bottom of a thread. Posts to messages.store
  and clears itself on success; the new message arrives back via Inertia (and
  live over Echo for other participants).
-->
<script setup>
import { useForm } from '@inertiajs/vue3'
import { Button } from "@/Components/UI/button/index.js";
import RichTextEditor from '@/Components/RichTextEditor.vue'

const props = defineProps({
  topic_id: { type: [Number, String], required: true },
})

const form = useForm({
  topic_id: props.topic_id,
  content: '',
})

const submit = () => {
  if (!form.content.trim()) {
    return
  }

  form.post(route('messages.store'), {
    preserveScroll: true,
    onSuccess: () => form.reset('content'),
  })
}
</script>

<template>
  <form
      class="space-y-2"
      @submit.prevent="submit"
  >
    <RichTextEditor
        id="message_content"
        v-model="form.content"
    />
    <p
        v-if="form.errors.content"
        class="text-sm text-destructive"
    >
      {{ form.errors.content }}
    </p>
    <div class="flex justify-end">
      <Button
          type="submit"
          size="sm"
          :disabled="form.processing"
      >
        Send
      </Button>
    </div>
  </form>
</template>
