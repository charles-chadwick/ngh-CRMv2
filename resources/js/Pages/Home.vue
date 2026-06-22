<script setup>

import AppLayout from "../Layouts/AppLayout.vue";
import { Card } from "@/Components/UI/card/index.js";

defineProps({
  my_tasks: { type: Array, default: () => [] },
})
</script>

<template>
<AppLayout>
  <h1 class="heading">Home</h1>
  <Card class="p-4">
  <p>Welcome to nghCRM-v2!</p>
  </Card>
  <Card class="mt-4 p-4">
    <h2 class="mb-2 font-bold">My Overdue Tasks</h2>
    <ul class="space-y-1 text-sm">
      <li v-for="task in my_tasks" :key="task.id" class="flex justify-between">
        <a :href="route('tasks.index')" class="hover:text-primary">{{ task.title }}</a>
        <span class="text-destructive">{{ new Date(task.due_at).toLocaleDateString() }}</span>
      </li>
      <li v-if="!my_tasks.length" class="text-muted-foreground">No overdue tasks 🎉</li>
    </ul>
  </Card>
</AppLayout>
</template>

<style scoped>

</style>
