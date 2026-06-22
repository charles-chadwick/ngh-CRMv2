<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from "@/Layouts/AppLayout.vue";
import Pagination from "@/Components/Pagination.vue";
import Avatar from "@/Components/Avatar.vue";
import StartTopicButton from "@/Components/Topics/StartTopicButton.vue";
import { Card } from "@/Components/UI/card/index.js";

defineProps({
  topics: { type: Object, required: true },
})

const participantsOf = (topic) => [...(topic.users ?? []), ...(topic.customers ?? [])]

const snippetOf = (topic) => {
  const last = topic.messages?.[0]
  if (!last) {
    return 'No messages yet.'
  }
  const text = last.content.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim()
  return text.length > 120 ? `${text.slice(0, 120)}…` : text
}
</script>

<template>
  <AppLayout>
    <Head title="Messages" />

    <div class="flex items-center justify-between my-6">
      <h1 class="text-2xl font-bold">Messages</h1>
      <StartTopicButton :href="route('topics.create')" />
    </div>

    <div
        v-if="topics.data.length"
        class="space-y-3"
    >
      <Card
          v-for="topic in topics.data"
          :key="topic.id"
          class="px-6 py-4"
      >
        <Link
            :href="route('topics.show', topic.id)"
            class="flex items-center justify-between gap-4 hover:text-primary"
        >
          <div class="min-w-0">
            <div class="flex items-center gap-2">
              <span class="font-bold">{{ topic.title }}</span>
              <span
                  v-if="topic.unread_count > 0"
                  class="rounded-full bg-primary px-2 text-xs font-bold text-primary-foreground"
              >
                {{ topic.unread_count }}
              </span>
            </div>
            <p class="truncate text-sm text-muted-foreground">{{ snippetOf(topic) }}</p>
          </div>
          <div class="flex shrink-0 -space-x-2">
            <Avatar
                v-for="participant in participantsOf(topic)"
                :key="`${participant.id}-${participant.full_name}`"
                :name="participant.full_name"
                :src="participant.avatar_url"
                size="xs"
                class="ring-2 ring-background"
            />
          </div>
        </Link>
      </Card>
    </div>
    <p
        v-else
        class="text-muted-foreground"
    >
      No conversations yet.
    </p>

    <Pagination :paginator="topics" class="mt-4" />
  </AppLayout>
</template>
