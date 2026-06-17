<!--suppress JSUnresolvedReference, NpmUsedModulesInstalled -->
<script setup>
import { Link } from '@inertiajs/vue3'
import Avatar from "../../Components/Avatar.vue";
import AppLayout from "../../Layouts/AppLayout.vue";
import Pagination from "../../Components/Pagination.vue";
import Search from "../../Components/Search.vue";
import { Card, CardHeader, CardContent } from "@/Components/UI/card/index.js";

defineProps ( {
  customers: Object
} )
</script>

<template>
  <AppLayout>
    <h1 class="text-2xl font-bold my-6">Customers</h1>
    <Search
        :route="route('customers.index')"
        placeholder="Search customers..."
        class="mb-4"
    />
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
      <Card
          v-for="customer in customers.data"
          :key="customer.id"
      >
        <CardHeader class="border-b border-b-primary [.border-b]:mx-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <Avatar
                  :name="customer.full_name"
                  :src="customer.avatar_url"
                  size="sm"
              />
              <Link
                  :href="route('customers.show', customer.id)"
                  class="hover:text-primary"
              >
                <div class="font-bold">{{ customer.full_name }}</div>
                <div class="text-sm text-muted-foreground">{{ customer.role }}</div>
              </Link>
            </div>
            <div class="text-sm text-muted-foreground text-right">
              <p class="text-foreground">{{ customer.email }}</p>
              <Link
                  v-if="customer.company"
                  :href="route('companies.show', customer.company.id)"
                  class="text-sm text-muted-foreground hover:text-primary"
              >
                {{ customer.company.title }}
              </Link>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <p>{{ customer.title || '—' }}</p>
        </CardContent>
      </Card>
    </div>
    <Pagination :paginator="customers" />
  </AppLayout>
</template>
