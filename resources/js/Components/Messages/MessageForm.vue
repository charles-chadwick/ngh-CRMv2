<!--
  MessageForm
  -----------
  Inline composer matching the note form: a rich-text body and Save/Cancel.
  Creating posts to messages.store with the polymorphic owner; editing puts to
  messages.update. No subject/type — a message is just content.
-->
<script setup>
import { useForm } from '@inertiajs/vue3'
import { Button } from "@/Components/UI/button/index.js";
import { Card, CardContent } from "@/Components/UI/card/index.js";
import RichTextEditor from '@/Components/RichTextEditor.vue'

const props = defineProps({
  messageable_type: { type: String, required: true },
  messageable_id: { type: [Number, String], required: true },
  message: { type: Object, default: null },
})

const emits = defineEmits(['saved', 'cancel'])

const form = useForm({
  model_type: props.messageable_type,
  model_id: props.messageable_id,
  content: props.message?.content || '',
})

const submit = () => {
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      if (!props.message) {
        form.reset('content')
      }
      emits('saved')
    },
  }

  if (props.message) {
    form.put(route('messages.update', props.message.id), options)
  } else {
    form.post(route('messages.store'), options)
  }
}
</script>

<template>
  <Card>
    <CardContent>
      <form
          class="space-y-3"
          @submit.prevent="submit"
      >
        <div class="space-y-2">
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
        </div>

        <div class="flex justify-end gap-3">
          <Button
              type="button"
              variant="ghost"
              size="sm"
              @click="emits('cancel')"
          >
            Cancel
          </Button>
          <Button
              type="submit"
              size="sm"
              :disabled="form.processing"
          >
            {{ message ? 'Update Message' : 'Save Message' }}
          </Button>
        </div>
      </form>
    </CardContent>
  </Card>
</template>
