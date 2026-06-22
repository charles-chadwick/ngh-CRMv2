<!--suppress JSUnresolvedReference -->
<script setup>
import Avatar from "../../Components/Avatar.vue";
import AppLayout from "../../Layouts/AppLayout.vue";
import Pagination from "../../Components/Pagination.vue";
import Search from "../../Components/Search.vue";
import { Card, CardHeader, CardContent } from "@/Components/UI/card/index.js";
import SortDropdown from "@/Components/SortDropdown.vue";
import ViewToggle from "@/Components/ViewToggle.vue";
import { useViewMode } from "@/composables/useViewMode.js";

defineProps ( { companies: Object } )

const { view, grid_class } = useViewMode('companies_view')
</script>

<template>
  <AppLayout>
    <h1 class="heading">Companies</h1>
    <div class="mb-4 flex flex-col gap-4 px-4 sm:flex-row sm:items-center">
      <Search
          :route="route('companies.index')"
          placeholder="Search companies..."
      />
      <div class="flex items-center gap-2">
        <SortDropdown
            :route="route('companies.index')"
            :options="[
                { label: 'Name', value: 'title' },
                { label: '# of Customers', value: 'customers_count' },
            ]"
        />
        <ViewToggle v-model="view" />
      </div>
    </div>
    <div
        class="grid gap-4"
        :class="grid_class"
    >
      <Card
          v-for="company in companies.data"
          :key="company.id"
      >
        <CardHeader class="border-b border-b-primary [.border-b]:mx-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <Avatar :name="company.title" :src="company.avatar_url" size="sm" />
              <a :href="route('companies.show', company)" class="hover:text-primary">
                <div class="font-bold">{{ company.title }}</div>
                <div class="text-sm text-muted-foreground">{{ company.type?.name }}</div>
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
