<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import Search from '@/Components/Search.vue'
import SortDropdown from '@/Components/SortDropdown.vue'
import { Button } from '@/Components/UI/button/index.js'
import { Card, CardHeader, CardContent } from '@/Components/UI/card/index.js'
import StatusBadge from '@/Components/StatusBadge.vue'

defineProps({
  leads: Object,
})
</script>

<template>
  <AppLayout>
    <div class="flex items-center justify-between my-6">
      <h1 class="text-2xl font-bold">Leads</h1>
      <Button as-child variant="stone">
        <Link :href="route('leads.create')">New Lead</Link>
      </Button>
    </div>
    <div class="mb-4 flex flex-col gap-4 px-4 sm:flex-row sm:items-center">
      <Search
          :route="route('leads.index')"
          placeholder="Search leads..."
      />
      <SortDropdown
          :route="route('leads.index')"
          :options="[
              { label: 'Title', value: 'title' },
              { label: 'Status', value: 'status' },
              { label: 'Type', value: 'type' },
              { label: 'Created', value: 'created_at' },
          ]"
      />
    </div>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <Card
          v-for="lead in leads.data"
          :key="lead.id"
      >
        <CardHeader class="border-b border-b-primary [.border-b]:mx-4">
          <div class="flex items-center justify-between">
            <Link
                :href="route('leads.show', lead.id)"
                class="font-bold hover:text-primary"
            >
              {{ lead.title }}
            </Link>
            <StatusBadge :status="lead.status" />
          </div>
        </CardHeader>
        <CardContent>
          <Link
              v-if="lead.company"
              :href="route('companies.show', lead.company.id)"
              class="text-sm font-medium hover:text-primary"
          >
            {{ lead.company.title }}
          </Link>
          <p class="text-sm text-muted-foreground">{{ lead.type }}</p>
          <p class="text-sm">{{ lead.assignee?.full_name || 'Unassigned' }}</p>
        </CardContent>
      </Card>
    </div>
    <Pagination :paginator="leads" />
  </AppLayout>
</template>
