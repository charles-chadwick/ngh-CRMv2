<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Head, Link } from '@inertiajs/vue3'
import { Card, CardHeader, CardContent } from "@/Components/UI/card/index.js";

const sections = [
  {
    title: 'Customer Roles',
    description: 'Manage the roles that can be assigned to customers.',
    route: route('settings.customer-roles.index'),
  },
]
</script>

<template>
  <AppLayout>
    <Head title="Settings" />
    <h1 class="text-2xl font-bold my-6">Settings</h1>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
      <Link
          v-for="section in sections"
          :key="section.route"
          :href="section.route"
      >
        <Card class="h-full hover:border-primary transition-colors">
          <CardHeader>
            <h2 class="font-bold">{{ section.title }}</h2>
          </CardHeader>
          <CardContent>
            <p class="text-sm text-muted-foreground">{{ section.description }}</p>
          </CardContent>
        </Card>
      </Link>
    </div>
  </AppLayout>
</template>
