<!--suppress JSUnresolvedReference, NpmUsedModulesInstalled -->
<script setup>
import { Link } from '@inertiajs/vue3'
import Avatar from "../../Components/Avatar.vue";
import AppLayout from "../../Layouts/AppLayout.vue";
import Pagination from "../../Components/Pagination.vue";
import Search from "../../Components/Search.vue";
import { Card, CardHeader, CardContent } from "@/Components/UI/card/index.js";
import { Button } from "@/Components/UI/button/index.js";
import SortDropdown from "@/Components/SortDropdown.vue";
import ViewToggle from "@/Components/ViewToggle.vue";
import { useViewMode } from "@/composables/useViewMode.js";
import { useDate } from "@/composables/useDate.js";

defineProps ( {
  customers: Object
} )

const { view, grid_class } = useViewMode('customers_view')
const { dateString } = useDate()
</script>

<template>
  <AppLayout>
    <div class="flex items-center justify-between my-6">
      <h1 class="text-2xl font-bold">Customers</h1>
      <Button as-child variant="stone">
        <Link :href="route('customers.create')">New Customer</Link>
      </Button>
    </div>
    <div class="mb-4 flex flex-col gap-4 px-4 sm:flex-row sm:items-center">
      <Search
          :route="route('customers.index')"
          placeholder="Search customers..."
      />
      <div class="flex items-center gap-2">
        <SortDropdown
            :route="route('customers.index')"
            :options="[
                { label: 'First Name', value: 'first_name' },
                { label: 'Last Name', value: 'last_name' },
                { label: 'Email', value: 'email' },
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
          v-for="customer in customers.data"
          :key="customer.id"
      >
        <CardHeader class="border-b border-b-primary [.border-b]:mx-4">
          <div class="flex items-center justify-between">
            <div class="flex items-start gap-2">
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
                <p>{{ dateString(customer.date_of_birth) }}</p>
              </Link>
            </div>

          </div>
        </CardHeader>
        <CardContent>
          <div class="text-sm text-muted-foreground">{{ customer.role?.name }}</div>
          <Link
              v-if="customer.company"
              :href="route('companies.show', customer.company.id)"
              class="text-sm text-muted-foreground hover:text-primary"
          >
            {{ customer.company.title }}
          </Link>
        </CardContent>
      </Card>
    </div>
    <Pagination :paginator="customers" />
  </AppLayout>
</template>
