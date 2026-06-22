<!--
  UserMenu
  --------
  Navbar control for the authenticated user. Shows the current user's avatar and
  name; opening it reveals a Log out action that POSTs to the logout route.
-->
<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import Avatar from "@/Components/Avatar.vue";
import { ChevronDown, LogOut } from "lucide-vue-next"

const page = usePage()

const open = ref(false)

const toggle = () => {
  open.value = !open.value
}

const logout = () => {
  router.post(route('logout'))
}

// Close when clicking outside the control.
const root = ref(null)
const onDocumentClick = (event) => {
  if (root.value && !root.value.contains(event.target)) {
    open.value = false
  }
}

onMounted(() => document.addEventListener('click', onDocumentClick))
onBeforeUnmount(() => document.removeEventListener('click', onDocumentClick))
</script>

<template>
  <div ref="root" class="relative">
    <button
        type="button"
        class="flex items-center gap-2 rounded-lg px-2 py-1 hover:bg-white/10"
        aria-label="Account menu"
        @click="toggle"
    >
      <Avatar
          :name="page.props.auth?.user?.full_name"
          :src="page.props.auth?.user?.avatar_url"
          size="xs"
      />
      <span class="hidden text-sm font-bold sm:inline">{{ page.props.auth?.user?.full_name }}</span>
      <ChevronDown class="h-4 w-4" />
    </button>

    <div
        v-if="open"
        class="absolute right-0 z-50 mt-2 w-48 rounded-lg border border-border bg-popover p-2 text-popover-foreground shadow-lg"
    >
      <button
          type="button"
          class="flex w-full items-center gap-2 rounded-md px-2 py-2 text-left text-sm hover:bg-muted"
          @click="logout"
      >
        <LogOut class="h-4 w-4" />
        <span>Log out</span>
      </button>
    </div>
  </div>
</template>
