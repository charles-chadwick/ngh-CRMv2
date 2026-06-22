<!--
  ActAsSwitcher
  -------------
  Dev-only navbar control for impersonating a user or customer. Shows the current
  actor; opening it reveals a type-ahead over users and customers. Selecting one
  POSTs to act-as.store, which records the choice in the session and reloads the
  page as that actor. Not a real login — purely for testing.
-->
<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import Avatar from "@/Components/Avatar.vue";
import { ChevronDown } from "lucide-vue-next"

const page = usePage()

const open = ref(false)
const query = ref('')
const results = ref([])
let debounce = null

const search = async (term) => {
  const res = await fetch(route('act-as.options', { q: term ?? '' }), {
    headers: { Accept: 'application/json' },
  })
  results.value = await res.json()
}

watch(query, (term) => {
  clearTimeout(debounce)
  debounce = setTimeout(() => search(term), 200)
})

const toggle = () => {
  open.value = !open.value
  if (open.value) {
    search(query.value)
  }
}

const switchTo = (person) => {
  router.post(route('act-as.store'), { type: person.type, id: person.id }, {
    onFinish: () => {
      open.value = false
      query.value = ''
    },
  })
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
        aria-label="Switch acting user"
        @click="toggle"
    >
      <Avatar
          :name="page.props.auth?.user?.full_name"
          :src="page.props.auth?.user?.avatar_url"
          size="xs"
      />
      <span class="hidden text-sm font-bold sm:inline">{{ page.props.auth?.user?.full_name ?? 'Guest' }}</span>
      <span
          v-if="page.props.auth?.user"
          class="hidden rounded-full bg-primary px-2 text-xs font-bold capitalize text-primary-foreground sm:inline"
      >
        {{ page.props.auth.user.type }}
      </span>
      <ChevronDown class="h-4 w-4" />
    </button>

    <div
        v-if="open"
        class="absolute right-0 z-50 mt-2 w-72 rounded-lg border border-border bg-popover p-2 text-popover-foreground shadow-lg"
    >
      <p class="px-2 pb-2 text-xs text-muted-foreground">Act as (dev only)</p>
      <input
          v-model="query"
          type="text"
          placeholder="Search users and customers..."
          class="w-full rounded-md border border-border bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"
      >
      <ul class="mt-1 max-h-72 overflow-y-auto">
        <li
            v-for="person in results"
            :key="`${person.type}:${person.id}`"
        >
          <button
              type="button"
              class="flex w-full items-center gap-2 rounded-md px-2 py-2 text-left text-sm hover:bg-muted"
              @click="switchTo(person)"
          >
            <Avatar :name="person.full_name" :src="person.avatar_url" size="xs" />
            <span class="truncate">{{ person.full_name }}</span>
            <span class="ml-auto text-xs text-muted-foreground capitalize">{{ person.type }}</span>
          </button>
        </li>
      </ul>
    </div>
  </div>
</template>
