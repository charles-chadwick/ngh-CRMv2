<!--suppress JSUnresolvedReference -->
<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from "../../Layouts/AppLayout.vue";
import Avatar from "../../Components/Avatar.vue";

defineProps ( {
  user: Object,
} )
</script>

<template>
  <AppLayout>
    <Head title="Show" />
    <div class="flex items-center justify-between mb-6">
      <div class="flex flex-row gap-4">
        <Avatar
            :name="`${user.first_name} ${user.last_name}`"
            :src="user.avatar_url"
            size="md"
        />
        <div>
        <h1 class="text-2xl font-bold">{{ user.first_name + ' ' + user.last_name }}</h1>
          <p class="text-stone-700">{{ user.email}}</p>
          <p class="text-stone-700">{{ user.role }}</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
