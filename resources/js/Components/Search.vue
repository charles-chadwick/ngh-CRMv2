<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { MagnifyingGlassIcon, XMarkIcon } from '@heroicons/vue/20/solid'
import { useDebounceFn } from '@vueuse/core'

const props = defineProps({
  route: {
    type: String,
    required: true
  },
  placeholder: {
    type: String,
    default: 'Search...'
  },
  param_name: {
    type: String,
    default: 'search'
  }
})

const url_params = new URLSearchParams(window.location.search)
const search_query = ref(url_params.get(props.param_name) || '')

const debouncedSearch = useDebounceFn(() => {
  performSearch()
}, 300)

watch(search_query, () => {
  debouncedSearch()
})

function performSearch() {
  const params = {}
  const url_params = new URLSearchParams(window.location.search)

  if (url_params.has('sort_by')) {
    params.sort_by = url_params.get('sort_by')
  }
  if (url_params.has('sort_direction')) {
    params.sort_direction = url_params.get('sort_direction')
  }

  if (search_query.value) {
    params[props.param_name] = search_query.value
  }

  router.get(props.route, params, { preserveState: true })
}

function clearSearch() {
  search_query.value = ''
}
</script>

<template>
  <div class="relative">
    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
      <MagnifyingGlassIcon class="h-5 w-5 text-muted-foreground" />
    </div>
    <input
        v-model="search_query"
        type="text"
        :placeholder="placeholder"
        class="block w-full rounded-md border-0 py-2 pl-10 pr-10 bg-stone-100/90 shadow-sm text-foreground ring-1 ring-inset ring-input placeholder:text-muted-foreground focus:ring-2 focus:ring-inset focus:ring-ring"
    />
    <button
        v-if="search_query"
        type="button"
        class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted-foreground hover:text-foreground"
        @click="clearSearch"
    >
      <span class="sr-only">Clear search</span>
      <XMarkIcon class="h-5 w-5" />
    </button>
  </div>
</template>