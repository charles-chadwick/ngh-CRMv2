<script setup>
import { ref, watch } from 'vue'
import Avatar from '@/Components/Avatar.vue'

const props = defineProps({
  initialLabel: { type: String, default: '' },
})
const emit = defineEmits(['update:modelType', 'update:modelId'])

const query = ref(props.initialLabel)
const results = ref([])
const open = ref(false)
let debounce = null

const search = async (term) => {
  const res = await fetch(route('tasks.assignees', { q: term ?? '' }), { headers: { Accept: 'application/json' } })
  results.value = await res.json()
}

watch(query, (term) => {
  clearTimeout(debounce)
  open.value = true
  debounce = setTimeout(() => search(term), 200)
})

const choose = (person) => {
  emit('update:modelType', person.type)
  emit('update:modelId', person.id)
  query.value = person.full_name
  open.value = false
}

const clear = () => {
  emit('update:modelType', null)
  emit('update:modelId', null)
  query.value = ''
}
</script>

<template>
  <div class="relative">
    <input
        v-model="query"
        type="text"
        placeholder="Search users and customers..."
        class="w-full rounded-md border border-border bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"
        @focus="open = true"
    >
    <button v-if="query" type="button" class="absolute right-2 top-2 text-xs text-muted-foreground" @click="clear">clear</button>
    <ul v-if="open && results.length" class="absolute z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-md border border-border bg-popover p-1 shadow-lg">
      <li v-for="person in results" :key="`${person.type}:${person.id}`">
        <button type="button" class="flex w-full items-center gap-2 rounded-md px-2 py-2 text-left text-sm hover:bg-muted" @click="choose(person)">
          <Avatar :name="person.full_name" :src="person.avatar_url" size="xs" />
          <span class="truncate">{{ person.full_name }}</span>
          <span class="ml-auto text-xs text-muted-foreground capitalize">{{ person.type }}</span>
        </button>
      </li>
    </ul>
  </div>
</template>
