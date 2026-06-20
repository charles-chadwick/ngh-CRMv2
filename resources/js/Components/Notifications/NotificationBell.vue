<!--
  NotificationBell
  ----------------
  Header bell showing unread @mention notifications. Initial list + count come
  from Inertia shared props; live updates arrive over Echo and prepend to the
  local list without a page reload. A self-removing toast is shown on arrival.
-->
<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { Bell } from 'lucide-vue-next'

const open = ref(false)
const page = usePage()

// Shared-prop computeds used only as the initial seed.
const seed_notifications = computed(() => page.props.notifications?.unread ?? [])
const seed_unread_count = computed(() => page.props.notifications?.unread_count ?? 0)

// Local reactive state: seeded on mount, extended by live pushes.
const live = reactive({ items: [], count: 0 })

const toast = (message) => {
    if (typeof document === 'undefined') {
        return
    }
    const el = document.createElement('div')
    el.textContent = message
    el.className = 'fixed bottom-4 right-4 z-50 rounded-lg bg-popover px-4 py-3 text-sm shadow-lg border border-border'
    document.body.appendChild(el)
    setTimeout(() => el.remove(), 5000)
}

onMounted(() => {
    live.items = [...seed_notifications.value]
    live.count = seed_unread_count.value

    const user_id = page.props.auth?.user?.id
    if (!user_id || !window.Echo) {
        return
    }

    window.Echo.private(`App.Models.User.${user_id}`)
        .notification((payload) => {
            live.items.unshift({ id: payload.id, data: payload })
            live.count += 1
            toast(`${payload.mentioner_name} mentioned you on ${payload.parent_label}`)
        })
})

onBeforeUnmount(() => {
    const user_id = page.props.auth?.user?.id
    if (user_id && window.Echo) {
        window.Echo.leave(`App.Models.User.${user_id}`)
    }
})

const markRead = (notification) => {
    router.patch(route('notifications.read', notification.id), {}, {
        preserveScroll: true,
        onSuccess: () => router.visit(notification.data.url),
    })
}

const markAllRead = () => {
    router.patch(route('notifications.readAll'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            live.items = []
            live.count = 0
        },
    })
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
          v-if="live.count > 0"
          class="absolute -right-2 -top-2 rounded-full bg-primary px-1.5 text-xs font-bold text-primary-foreground"
      >
        {{ live.count }}
      </span>
    </button>

    <div
        v-if="open"
        class="absolute right-0 z-50 mt-2 w-80 rounded-lg border border-border bg-popover p-2 text-popover-foreground shadow-lg"
    >
      <div class="flex items-center justify-between px-2 py-1">
        <span class="font-bold">Notifications</span>
        <button
            v-if="live.count > 0"
            type="button"
            class="text-xs text-muted-foreground hover:text-primary"
            @click="markAllRead"
        >
          Mark all read
        </button>
      </div>

      <p
          v-if="live.items.length === 0"
          class="px-2 py-4 text-center text-sm text-muted-foreground"
      >
        You're all caught up.
      </p>

      <button
          v-for="notification in live.items"
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
