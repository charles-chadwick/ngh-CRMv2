<script setup>
import { computed, onBeforeUnmount, ref } from 'vue'
import Avatar from '@/Components/Avatar.vue'
import { Button } from '@/Components/UI/button/index.js'
import { useDate } from '@/Composables/useDate.js'

const props = defineProps({
  // The user who created the record (activity log causer). May be null for
  // records created before activity logging was enabled.
  user: { type: Object, default: null },
  // The parent model's created_at timestamp.
  date: { type: String, default: null },
  show_avatar: { type: Boolean, default: true },
})

const { diffForHumans, dateTimeString } = useDate()

const creator_name = computed(() => props.user?.full_name ?? 'Unknown')

const profile_url = computed(() =>
    props.user ? route('users.show', props.user.id) : null,
)

const is_hovering = ref(false)
let hide_timer = null

const showCard = () => {
  if (hide_timer) {
    clearTimeout(hide_timer)
    hide_timer = null
  }

  if (props.user) {
    is_hovering.value = true
  }
}

const hideCard = () => {
  hide_timer = setTimeout(() => {
    is_hovering.value = false
  }, 150)
}

onBeforeUnmount(() => {
  if (hide_timer) {
    clearTimeout(hide_timer)
  }
})
</script>

<template>
  <p class="relative inline-flex items-center gap-1.5 text-sm text-muted-foreground">
    <span
        class="inline-flex items-center gap-1.5"
        @mouseenter="showCard"
        @mouseleave="hideCard"
        @focusin="showCard"
        @focusout="hideCard"
    >
      <Avatar
          v-if="user?.avatar_url && show_avatar"
          :name="creator_name"
          :src="user.avatar_url"
          size="xs"
      />
      Created by
      <a
          v-if="profile_url"
          :href="profile_url"
          target="_blank"
          rel="noopener noreferrer"
          class="font-medium text-foreground hover:text-primary hover:underline"
      >
        {{ creator_name }}
      </a>
      <span v-else>{{ creator_name }}</span>

      <!-- Hover card with user details -->
      <span
          v-if="user && is_hovering"
          class="absolute bottom-full left-0 z-50 mb-2 w-64 rounded-lg border border-border bg-popover p-4 text-popover-foreground shadow-lg"
          role="dialog"
      >
        <span class="flex items-center gap-3">
          <Avatar
              :name="creator_name"
              :src="user.avatar_url"
              :full-src="user.avatar_full_url"
              size="sm"
          />
          <span class="flex min-w-0 flex-col">
            <span class="truncate font-bold text-foreground">{{ creator_name }}</span>
            <span
                v-if="user.role"
                class="text-xs text-muted-foreground"
            >
              {{ user.role }}
            </span>
            <span
                v-if="user.email"
                class="truncate text-xs text-muted-foreground"
            >
              {{ user.email }}
            </span>
          </span>
        </span>
        <Button
            as-child
            variant="outline"
            size="sm"
            class="mt-3 w-full"
        >
          <a
              :href="profile_url"
              target="_blank"
              rel="noopener noreferrer"
          >
            View profile ↗
          </a>
        </Button>
      </span>
    </span>

    <span
        v-if="date"
        :title="dateTimeString(date)"
    >
      · {{ diffForHumans(date) }}
    </span>
  </p>
</template>
