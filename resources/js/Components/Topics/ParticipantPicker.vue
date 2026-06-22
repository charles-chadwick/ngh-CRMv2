<!--
  ParticipantPicker
  -----------------
  Type-ahead multi-select for topic participants. Searches users and customers
  via topics.participants and emits the selection as an array of
  { type, id, full_name, avatar_url } through v-model.
-->
<script setup>
import { ref, watch } from 'vue'
import Avatar from "@/Components/Avatar.vue";
import { Input } from "@/Components/UI/input/index.js";
import { X } from "lucide-vue-next"

const selected = defineModel({ type: Array, default: () => [] })

const query = ref('')
const results = ref([])
const searching = ref(false)
let debounce = null

const keyFor = (participant) => `${participant.type}:${participant.id}`

const isSelected = (participant) =>
    selected.value.some((item) => keyFor(item) === keyFor(participant))

const search = async (term) => {
  searching.value = true
  try {
    const res = await fetch(route('topics.participants', { q: term ?? '' }), {
      headers: { Accept: 'application/json' },
    })
    results.value = await res.json()
  } finally {
    searching.value = false
  }
}

watch(query, (term) => {
  clearTimeout(debounce)

  // An empty query (including the reset after picking someone) closes the list
  // rather than re-fetching, so the dropdown can't cover the rest of the form.
  if (!term) {
    results.value = []
    return
  }

  debounce = setTimeout(() => search(term), 200)
})

const add = (participant) => {
  if (!isSelected(participant)) {
    selected.value = [...selected.value, participant]
  }
  query.value = ''
  results.value = []
}

const remove = (participant) => {
  selected.value = selected.value.filter((item) => keyFor(item) !== keyFor(participant))
}

// Close the results list when focus leaves the field, with a short delay so a
// click on an option still registers before the list is torn down.
const close = () => {
  setTimeout(() => {
    results.value = []
  }, 150)
}
</script>

<template>
  <div class="space-y-2">
    <div
        v-if="selected.length"
        class="flex flex-wrap gap-2"
    >
      <span
          v-for="participant in selected"
          :key="keyFor(participant)"
          class="inline-flex items-center gap-2 rounded-full bg-muted px-3 py-1 text-sm"
      >
        <Avatar :name="participant.full_name" :src="participant.avatar_url" size="xs" />
        {{ participant.full_name }}
        <span class="text-xs text-muted-foreground capitalize">{{ participant.type }}</span>
        <button
            type="button"
            class="text-muted-foreground hover:text-destructive"
            aria-label="Remove participant"
            @click="remove(participant)"
        >
          <X class="h-3 w-3" />
        </button>
      </span>
    </div>

    <div class="relative">
      <Input
          v-model="query"
          type="text"
          placeholder="Search users and customers..."
          @focus="search(query)"
          @blur="close"
      />
      <ul
          v-if="results.length"
          class="absolute z-20 mt-1 w-full overflow-hidden rounded-lg border border-border bg-popover shadow-lg"
      >
        <li
            v-for="participant in results"
            :key="keyFor(participant)"
        >
          <button
              type="button"
              class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-muted disabled:opacity-40"
              :disabled="isSelected(participant)"
              @click="add(participant)"
          >
            <Avatar :name="participant.full_name" :src="participant.avatar_url" size="xs" />
            <span>{{ participant.full_name }}</span>
            <span class="ml-auto text-xs text-muted-foreground capitalize">{{ participant.type }}</span>
          </button>
        </li>
      </ul>
    </div>
  </div>
</template>
