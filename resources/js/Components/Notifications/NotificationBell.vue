<!--
  NotificationBell
  ----------------
  Header bell showing unread @mention notifications. Initial list + count come
  from Inertia shared props; Phase 3 adds live updates over Echo. Clicking an
  item marks it read and navigates to the note's parent record.
-->
<script setup>
import { computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { Bell } from 'lucide-vue-next'

const open = ref(false)
const page = usePage()

const notifications = computed(() => page.props.notifications?.unread ?? [])
const unread_count = computed(() => page.props.notifications?.unread_count ?? 0)

const markRead = (notification) => {
  router.patch(route('notifications.read', notification.id), {}, {
    preserveScroll: true,
    onSuccess: () => router.visit(notification.data.url),
  })
}

const markAllRead = () => {
  router.patch(route('notifications.readAll'), {}, { preserveScroll: true })
}
</script>

<template>
  <div class="relative">
    <button
        type="button"
        class="relative"
        aria-label="Notifications"
        @click="open = !open"
    >
      <Bell class="h-5 w-5" />
      <span
          v-if="unread_count > 0"
          class="absolute -right-2 -top-2 rounded-full bg-primary px-1.5 text-xs font-bold text-primary-foreground"
      >
        {{ unread_count }}
      </span>
    </button>

    <div
        v-if="open"
        class="absolute right-0 z-50 mt-2 w-80 rounded-lg border border-border bg-popover p-2 text-popover-foreground shadow-lg"
    >
      <div class="flex items-center justify-between px-2 py-1">
        <span class="font-bold">Notifications</span>
        <button
            v-if="unread_count > 0"
            type="button"
            class="text-xs text-muted-foreground hover:text-primary"
            @click="markAllRead"
        >
          Mark all read
        </button>
      </div>

      <p
          v-if="notifications.length === 0"
          class="px-2 py-4 text-center text-sm text-muted-foreground"
      >
        You're all caught up.
      </p>

      <button
          v-for="notification in notifications"
          :key="notification.id"
          type="button"
          class="block w-full rounded-md px-2 py-2 text-left text-sm hover:bg-muted"
          @click="markRead(notification)"
      >
        <span class="font-medium">{{ notification.data.mentioner_name }}</span>
        mentioned you on
        <span class="font-medium">{{ notification.data.parent_label }}</span>
      </button>
    </div>
  </div>
</template>
