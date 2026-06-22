<!--
  Topics/Show
  -----------
  A single conversation thread. Server-rendered messages come in chronological
  order; new messages from other participants arrive live over the private
  `topics.{id}` Echo channel and append to the bottom. Mirrors the subscribe/
  leave lifecycle used by NotificationBell.
-->
<script setup>
import { computed, onBeforeUnmount, onMounted, reactive } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from "@/Layouts/AppLayout.vue";
import Avatar from "@/Components/Avatar.vue";
import Pagination from "@/Components/Pagination.vue";
import MessageItem from "@/Components/Topics/MessageItem.vue";
import MessageComposer from "@/Components/Topics/MessageComposer.vue";
import { Card } from "@/Components/UI/card/index.js";
import { ArrowLeft } from "lucide-vue-next"

const props = defineProps({
  topic: { type: Object, required: true },
  messages: { type: Object, required: true },
})

const participants = computed(() => [...(props.topic.users ?? []), ...(props.topic.customers ?? [])])

// Messages pushed live since mount; rendered after the server-provided page.
const live = reactive({ items: [] })
const channel = `topics.${props.topic.id}`

onMounted(() => {
  if (!window.Echo) {
    return
  }

  window.Echo.private(channel).listen('.MessagePosted', (payload) => {
    live.items.push(payload)
  })
})

onBeforeUnmount(() => {
  if (window.Echo) {
    window.Echo.leave(`private-${channel}`)
  }
})
</script>

<template>
  <AppLayout>
    <Head :title="topic.title" />

    <div class="mb-4 flex items-center gap-3">
      <Link
          :href="route('topics.index')"
          class="text-muted-foreground hover:text-primary"
          aria-label="Back to messages"
      >
        <ArrowLeft />
      </Link>
      <div>
        <h1 class="text-2xl font-bold">{{ topic.title }}</h1>
        <div class="mt-1 flex items-center gap-2">
          <Avatar
              v-for="participant in participants"
              :key="`${participant.id}-${participant.full_name}`"
              :name="participant.full_name"
              :src="participant.avatar_url"
              size="xs"
          />
          <span class="text-sm text-muted-foreground">{{ participants.length }} participants</span>
        </div>
      </div>
    </div>

    <Card class="px-6 py-4">
      <Pagination :paginator="messages" class="mb-2" />

      <div class="space-y-3">
        <MessageItem
            v-for="message in messages.data"
            :key="message.id"
            :message="message"
        />
        <MessageItem
            v-for="message in live.items"
            :key="`live-${message.id}`"
            :message="message"
        />
      </div>

      <hr class="my-4" />

      <MessageComposer :topic_id="topic.id" />
    </Card>
  </AppLayout>
</template>
