<!--
  UserAttribution
  ---------------
  A single "<label> <user> · <when>" line, e.g. "Created by Jane Doe · 2 days ago".

  Presentational building block used by <CreatedBy>. It renders:
    - an optional avatar,
    - the label + a link to the user's profile,
    - a hover card with the user's details (name, role, email, profile button),
    - a relative timestamp with the full date/time as a tooltip.

  It knows nothing about "created" vs "updated" — the caller passes the label.
-->
<script setup>
import { computed, onBeforeUnmount, ref } from 'vue'
import Avatar from '@/Components/Avatar.vue'
import { Button } from '@/Components/UI/button/index.js'
import { useDate } from '@/Composables/useDate.js'

const props = defineProps({
  // Leading text for the attribution, e.g. "Created by" or "Updated by".
  label: { type: String, required: true },
  // The user the action is attributed to (activity log causer). May be null
  // for records created before activity logging was enabled.
  user: { type: Object, default: null },
  // The timestamp associated with the action.
  date: { type: String, default: null },
  show_avatar: { type: Boolean, default: true },
})

// Date helpers: `diffForHumans` -> "2 days ago", `dateTimeString` -> tooltip.
const { diffForHumans, dateTimeString } = useDate()

// Fall back to "Unknown" for records created before activity logging existed.
const user_name = computed(() => props.user?.full_name ?? 'Unknown')

// Only link to a profile when we actually have a user to link to.
const profile_url = computed(() =>
    props.user ? route('users.show', props.user.id) : null,
)

// --- Hover card visibility -------------------------------------------------
// `hide_timer` adds a short grace period so moving the cursor from the trigger
// into the card itself doesn't immediately dismiss it.
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
  <!-- `relative` anchors the absolutely-positioned hover card below. -->
  <span class="relative inline-flex items-center gap-1.5">
    <!-- Trigger: hovering/focusing this region opens the hover card. -->
    <span
        class="inline-flex items-center gap-1.5"
        @mouseenter="showCard"
        @mouseleave="hideCard"
        @focusin="showCard"
        @focusout="hideCard"
    >
      <!-- Small inline avatar (suppressed on pages that pass show_avatar=false). -->
      <Avatar
          v-if="user?.avatar_url && show_avatar"
          :name="user_name"
          :src="user.avatar_url"
          size="xs"
      />
      {{ label }}
      <!-- Name links to the profile when known, otherwise plain text. -->
      <a
          v-if="profile_url"
          :href="profile_url"
          target="_blank"
          rel="noopener noreferrer"
          class="font-medium text-foreground hover:text-primary hover:underline"
      >
        {{ user_name }}
      </a>
      <span v-else>{{ user_name }}</span>

      <!-- Hover card: floats above the trigger with the user's full details. -->
      <span
          v-if="user && is_hovering"
          class="absolute bottom-full left-0 z-50 mb-2 w-64 rounded-lg border border-border bg-popover p-4 text-popover-foreground shadow-lg"
          role="dialog"
      >
        <span class="flex items-center gap-3">
          <Avatar
              :name="user_name"
              :src="user.avatar_url"
              :full-src="user.avatar_full_url"
              size="sm"
          />
          <span class="flex min-w-0 flex-col">
            <span class="truncate font-bold text-foreground">{{ user_name }}</span>
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

    <!-- Relative timestamp; full date/time shown on hover via the title. -->
    <span
        v-if="date"
        :title="dateTimeString(date)"
    >
      · {{ diffForHumans(date) }}
    </span>
  </span>
</template>
