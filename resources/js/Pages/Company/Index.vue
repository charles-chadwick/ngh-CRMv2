<script setup>
import AppLayout from "../../Layouts/AppLayout.vue";

defineProps ( { companies: Object } )
</script>

<template>
  <AppLayout>
    <h1 class="text-2xl font-bold my-6">Companies</h1>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
      <div
          v-for="company in companies.data"
          :key="company.id"
          class="p-4 rounded rounded-xl border border-stone-200"
      >
        <div class="flex justify-between  py-2 border-b-2 border-b-green-500">
          <div class="font-bold">{{ company.title }}</div>
          <div class="text-sm text-stone-500">{{ company.users_count }} users</div>
        </div>
        <p class="text-stone-800 py-2">{{ company.description }}</p>
      </div>
    </div>
  </AppLayout>
</template>
