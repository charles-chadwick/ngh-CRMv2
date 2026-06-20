<!--suppress JSUnresolvedReference -->
<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from "../../Layouts/AppLayout.vue";
import Avatar from "../../Components/Avatar.vue";
import { Button } from "@/Components/UI/button/index.js";
import { Card } from "@/Components/UI/card/index.js";
import { Pencil } from "lucide-vue-next";

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
            :full-src="user.avatar_full_url"
            size="md"
            clickable
        />
        <div>
        <h1 class="text-2xl font-bold">{{ user.first_name + ' ' + user.last_name }}</h1>
          <p class="text-muted-foreground">{{ user.email}}</p>
          <p class="text-muted-foreground">{{ user.role }}</p>
        </div>
      </div>

      <Button
          as-child
          variant="default"
          size="icon-sm"
          aria-label="Edit"
          title="Edit"
      >
        <Link :href="route('users.edit', user.id)"><Pencil /></Link>
      </Button>
    </div>
    <Card class="px-6">
Hi there
    </Card>

  </AppLayout>
</template>
