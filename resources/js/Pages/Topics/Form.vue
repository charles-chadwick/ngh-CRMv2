<!--
  Topics/Form
  -----------
  Start a new conversation: a title, a participant multi-select, and the opening
  message. Participants are sent as { type, id } pairs the controller resolves
  against its user/customer whitelist.
-->
<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from "@/Layouts/AppLayout.vue";
import { Card, CardContent } from "@/Components/UI/card/index.js";
import { Label } from "@/Components/UI/label/index.js";
import { Input } from "@/Components/UI/input/index.js";
import { Button } from "@/Components/UI/button/index.js";
import RichTextEditor from '@/Components/RichTextEditor.vue'
import ParticipantPicker from "@/Components/Topics/ParticipantPicker.vue";

const props = defineProps({
  preselected: { type: Object, default: null },
})

const participants = ref(props.preselected ? [props.preselected] : [])

const form = useForm({
  title: '',
  participants: [],
  content: '',
})

const submit = () => {
  form
      .transform((data) => ({
        ...data,
        participants: participants.value.map(({ type, id }) => ({ type, id })),
      }))
      .post(route('topics.store'), { preserveScroll: true })
}
</script>

<template>
  <AppLayout>
    <Head title="New conversation" />

    <h1 class="text-2xl font-bold my-6">New conversation</h1>

    <Card class="max-w-2xl">
      <CardContent>
        <form
            class="space-y-4"
            @submit.prevent="submit"
        >
          <div class="space-y-2">
            <Label for="topic_title">Subject</Label>
            <Input
                id="topic_title"
                v-model="form.title"
                type="text"
                placeholder="What's this about?"
            />
            <p v-if="form.errors.title" class="text-sm text-destructive">{{ form.errors.title }}</p>
          </div>

          <div class="space-y-2">
            <Label>Participants</Label>
            <ParticipantPicker v-model="participants" />
            <p v-if="form.errors.participants" class="text-sm text-destructive">{{ form.errors.participants }}</p>
          </div>

          <div class="space-y-2">
            <Label for="topic_message">Message</Label>
            <RichTextEditor
                id="topic_message"
                v-model="form.content"
            />
            <p v-if="form.errors.content" class="text-sm text-destructive">{{ form.errors.content }}</p>
          </div>

          <div class="flex justify-end">
            <Button
                type="submit"
                :disabled="form.processing"
            >
              Start conversation
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  </AppLayout>
</template>
