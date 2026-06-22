<!--
  MessageItem
  -----------
  A single message rendered as a card, mirroring the note card layout: the
  sanitized HTML body, a divider, then a footer attributing the author (avatar +
  name) with a relative timestamp. Authors may be a User or a Customer, so the
  attribution is self-contained rather than reusing the user-only CreatedBy.

  When `editable` is set, edit/remove actions are shown for the current actor's
  own messages (matching the note card), and bubble up via `edit`/`remove`.
-->
<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import Avatar from "@/Components/Avatar.vue";
import { Button } from "@/Components/UI/button/index.js";
import { Pencil, Trash2 } from "lucide-vue-next"
import { useDate } from "@/composables/useDate.js";

const props = defineProps({
  message: { type: Object, required: true },
  editable: { type: Boolean, default: false },
})

defineEmits(['edit', 'remove'])

const page = usePage()
const { diffForHumans, dateTimeString } = useDate()

const author_name = computed(() => props.message.author?.full_name ?? 'Unknown')

// The current actor as a polymorphic identity, so we only offer edit/remove on
// their own messages (the backend enforces the same rule).
const is_own = computed(() => {
  const actor = page.props.auth?.user
  if (!actor) {
    return false
  }
  const type = actor.type === 'customer' ? 'App\\Models\\Customer' : 'App\\Models\\User'
  return props.message.author_type === type && Number(props.message.author_id) === Number(actor.id)
})
</script>

<template>
  <div>
    <!-- Message body is sanitized HTML from the server; safe to render. -->
    <div
        class="message-content mt-2 text-sm"
        v-html="message.content"
    />
    <hr class="my-2" />
    <!-- Footer: author attribution, matching the note card footer styling. -->
    <div class="flex flex-col sm:flex-row p-4 justify-between items-center bg-stone-100">
      <span class="flex items-center gap-1.5 text-sm text-muted-foreground">
        <Avatar
            :name="author_name"
            :src="message.author?.avatar_url"
            size="xs"
        />
        <span class="font-medium text-foreground">{{ author_name }}</span>
        <span
            v-if="message.created_at"
            :title="dateTimeString(message.created_at)"
        >
          · {{ diffForHumans(message.created_at) }}
        </span>
      </span>
      <!-- Action buttons: only the author may edit/remove their own message. -->
      <div v-if="editable && is_own">
        <Button
            aria-label="Edit"
            size="icon-sm"
            title="Edit"
            variant="default"
            @click="$emit('edit', message)"
        >
          <Pencil />
        </Button>
        <Button
            aria-label="Remove"
            class="text-destructive hover:text-destructive"
            size="icon-sm"
            title="Remove"
            variant="ghost"
            @click="$emit('remove', message)"
        >
          <Trash2 />
        </Button>
      </div>
    </div>
  </div>
</template>
