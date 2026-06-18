<script setup>
import { computed } from 'vue'
import { Squares2X2Icon, ListBulletIcon } from '@heroicons/vue/24/outline'

// Controlled via v-model so the parent owns (and can persist) the value.
const model = defineModel({
  type: String,
  default: 'grid',
  validator: (value) => ['grid', 'list'].includes(value),
})

const is_grid_view = computed(() => model.value === 'grid')

function toggle() {
  model.value = is_grid_view.value ? 'list' : 'grid'
}
</script>

<template>
  <button
      type="button"
      class="inline-flex items-center gap-x-2 rounded-md bg-secondary px-3 py-2 font-medium text-secondary-foreground transition-colors hover:bg-secondary/80"
      @click="toggle"
  >
    <Squares2X2Icon v-if="!is_grid_view" class="h-5 w-5" />
    <ListBulletIcon v-else class="h-5 w-5" />
    {{ is_grid_view ? 'List' : 'Grid' }}
  </button>
</template>
