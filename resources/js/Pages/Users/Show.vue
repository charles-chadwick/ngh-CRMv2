<script setup>
  import { Head } from '@inertiajs/vue3'
  import AppLayout from "../../Layouts/AppLayout.vue";
  import Avatar from "../../Components/Avatar.vue";

  defineProps({
    user: Object,
  })
</script>

<template>
  <AppLayout>
    <Head title="Show" />
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold">User Details</h1>
      <Avatar
          :name="`${user.first_name} ${user.last_name}`"
          :src="user.avatar_url"
          size="lg"
      />
    </div>
  </AppLayout>
</template>
