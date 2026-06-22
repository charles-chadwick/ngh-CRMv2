<script setup>
import { ref, watch } from 'vue'

defineProps({ initialLabel: { type: String, default: '' } })
const emit = defineEmits(['update:modelType', 'update:modelId'])

const query = ref('')
const results = ref([])
const open = ref(false)
let debounce = null

const search = async (term) => {
  const res = await fetch(route('tasks.subjects', { q: term ?? '' }), { headers: { Accept: 'application/json' } })
  results.value = await res.json()
}
watch(query, (term) => {
  clearTimeout(debounce)
  open.value = true
  debounce = setTimeout(() => search(term), 200)
})
const choose = (subject) => {
  emit('update:modelType', subject.type)
  emit('update:modelId', subject.id)
  query.value = subject.label
  open.value = false
}
</script>

<template>
  <div class="relative">
    <input v-model="query" type="text" placeholder="Search companies, customers, leads..."
           class="w-full rounded-md border border-border bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"
           @focus="open = true">
    <ul v-if="open && results.length" class="absolute z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-md border border-border bg-popover p-1 shadow-lg">
      <li v-for="subject in results" :key="`${subject.type}:${subject.id}`">
        <button type="button" class="flex w-full items-center gap-2 rounded-md px-2 py-2 text-left text-sm hover:bg-muted" @click="choose(subject)">
          <span class="truncate">{{ subject.label }}</span>
          <span class="ml-auto text-xs text-muted-foreground capitalize">{{ subject.type }}</span>
        </button>
      </li>
    </ul>
  </div>
</template>
