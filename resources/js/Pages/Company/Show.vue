<script setup>
  import AppLayout from '../../Layouts/AppLayout.vue'
  import { Head } from '@inertiajs/vue3'
  import Avatar from "../../Components/Avatar.vue";
  import CustomerList from "../Customers/Partials/List.vue";

  defineProps({ company: Object})
</script>

<template>
  <AppLayout>
    <Head :title="company.title" />
    <div class="flex items-center justify-between mb-6">
      <div class="flex flex-row gap-4">
        <Avatar
            :name="company.title"
            :src="company.avatar_url"
            size="md"
        />
        <div>
          <h1 class="text-2xl font-bold">{{ company.title }}</h1>
          <p class="text-muted-foreground">{{ company.type}}</p>
          <p class="text-muted-foreground" v-html="company.description"></p>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <CustomerList :customers="company.customers" />
    </div>
  </AppLayout>
</template>
