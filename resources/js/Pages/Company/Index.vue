<!--suppress JSUnresolvedReference -->
<script setup>
import Avatar from "../../Components/Avatar.vue";
import AppLayout from "../../Layouts/AppLayout.vue";
import Pagination from "../../Components/Pagination.vue";

defineProps ( { companies: Object } )
</script>

<template>
  <AppLayout>
    <h1 class="text-2xl font-bold my-6">Companies</h1>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
      <div
          v-for="company in companies.data"
          :key="company.id"
          class="p-4 rounded-xl border border-stone-200"
      >
        <div class="flex items-center justify-between gap-2 py-2 border-b-2 border-b-green-500">
          <div class="flex items-center gap-2">
            <Avatar :name="company.title" :src="company.avatar_url" size="sm" />
            <div class="font-bold">{{ company.title }}</div>
          </div>
          <div class="text-sm text-stone-500">{{ company.customers_count }} customers</div>
        </div>
        <p class="text-stone-800 py-2">{{ company.description }}</p>
      </div>
      <Pagination :paginator="companies" />
    </div>
  </AppLayout>
</template>
