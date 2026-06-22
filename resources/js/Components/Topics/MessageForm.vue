<!--
  MessageForm
  -----------
  Inline composer matching the note form: a rich-text body and Save/Cancel.
  Creating posts to the customer's conversation (customers.messages.store);
  editing puts to messages.update. No subject/type — a message is just content.
-->
<script setup>
import { useForm } from '@inertiajs/vue3'
import { Button } from "@/Components/UI/button/index.js";
import { Card, CardContent } from "@/Components/UI/card/index.js";
import RichTextEditor from '@/Components/RichTextEditor.vue'

const props = defineProps({
  customer_id: { type: [Number, String], required: true },
  message: { type: Object, default: null },
})

const emits = defineEmits(['saved', 'cancel'])

const form = useForm({
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
    form.post(route('customers.messages.store', props.customer_id), options)
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
