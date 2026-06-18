<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/UI/select/index.js'
import { BarsArrowDownIcon, BarsArrowUpIcon } from '@heroicons/vue/20/solid'

const props = defineProps({
  options: {
    type: Array,
    required: true,
  },
  default_field: {
    type: String,
    default: '',
  },
  default_direction: {
    type: String,
    default: 'asc',
    validator: (value) => ['asc', 'desc'].includes(value),
  },
  route: {
    type: String,
    required: true,
  },
  // Prefix the query params so several SortDropdowns can live on one page
  // without clobbering each other (e.g. "customers" -> customers_sort_by).
  param_prefix: {
    type: String,
    default: '',
  },
})

// Static per instance — param_prefix is a prop that never changes.
const field_param = props.param_prefix ? `${props.param_prefix}_sort_by` : 'sort_by'
const direction_param = props.param_prefix ? `${props.param_prefix}_sort_direction` : 'sort_direction'

const url_params = new URLSearchParams(window.location.search)
const sort_by = ref(url_params.get(field_param) || props.default_field)
const sort_direction = ref(url_params.get(direction_param) || props.default_direction)

function toggleDirection() {
  sort_direction.value = sort_direction.value === 'asc' ? 'desc' : 'asc'
  handleSort()
}

function handleSort() {
  // Preserve every existing query param (search, paging, other sort
  // dropdowns) and only override this instance's params.
  const params = Object.fromEntries(new URLSearchParams(window.location.search))
  params[field_param] = sort_by.value
  params[direction_param] = sort_direction.value

  router.get(props.route, params, { preserveState: true, preserveScroll: true })
}
</script>

<template>
  <div class="flex items-center gap-x-2">
    <Select
        v-model="sort_by"
        @update:modelValue="handleSort"
    >
      <SelectTrigger class="w-48 bg-white">
        <SelectValue placeholder="Sort by..." />
      </SelectTrigger>
      <SelectContent>
        <SelectItem
            v-for="option in options"
            :key="option.value"
            :value="option.value"
        >
          {{ option.label }}
        </SelectItem>
      </SelectContent>
    </Select>
    <button
        type="button"
        class="inline-flex items-center justify-center rounded-md p-2 text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
        :title="sort_direction === 'asc' ? 'Ascending' : 'Descending'"
        @click="toggleDirection"
    >
      <span class="sr-only">
        {{ sort_direction === 'asc' ? 'Sort ascending' : 'Sort descending' }}
      </span>
      <BarsArrowUpIcon v-if="sort_direction === 'asc'" class="h-5 w-5" />
      <BarsArrowDownIcon v-else class="h-5 w-5" />
    </button>
  </div>
</template>
