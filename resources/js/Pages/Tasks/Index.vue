<script setup>
defineProps({
  overdue: { type: Array, default: () => [] },
  due_today: { type: Array, default: () => [] },
  upcoming: { type: Array, default: () => [] },
  calendar: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
  types: { type: Array, default: () => [] },
  statuses: { type: Array, default: () => [] },
})
</script>

<template>
  <div>
    <h1 class="text-xl font-bold">My Activities</h1>
  </div>
</template>
