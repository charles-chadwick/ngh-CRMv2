<!--suppress JSUnresolvedReference -->
<script setup>
import Avatar from "../../Components/Avatar.vue";
import AppLayout from "../../Layouts/AppLayout.vue";
import Pagination from "../../Components/Pagination.vue";
import { Card, CardHeader, CardContent } from "@/Components/UI/card/index.js";

defineProps ( { companies: Object } )
</script>

<template>
  <AppLayout>
    <h1 class="text-2xl font-bold my-6">Companies</h1>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
      <Card
          v-for="company in companies.data"
          :key="company.id"
      >
        <CardHeader class="border-b border-b-primary [.border-b]:pb-4">
          <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
              <Avatar :name="company.title" :src="company.avatar_url" size="sm" />
              <a :href="route('companies.show', company)" class="hover:text-primary">
                <div class="font-bold">{{ company.title }}</div>
              </a>
            </div>
            <div class="text-sm text-muted-foreground">{{ company.customers_count }} customers</div>
          </div>
        </CardHeader>
        <CardContent>
          <p class="text-foreground">{{ company.description }}</p>
        </CardContent>
      </Card>

    </div>
    <Pagination :paginator="companies" />
  </AppLayout>
</template>
