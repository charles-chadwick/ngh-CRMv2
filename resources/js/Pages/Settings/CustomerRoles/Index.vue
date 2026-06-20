<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Head, Link, router } from '@inertiajs/vue3'
import { Button } from "@/Components/UI/button/index.js";
import { Card } from "@/Components/UI/card/index.js";
import Pagination from "@/Components/Pagination.vue";
import { Pencil, Trash2 } from "lucide-vue-next";

defineProps({
  customer_roles: Object,
})

const destroy = (role) => {
  if (confirm(`Delete the "${role.name}" role?`)) {
    router.delete(route('settings.customer-roles.destroy', role.id), {
      preserveScroll: true,
    })
  }
}
</script>

<template>
  <AppLayout>
    <Head title="Customer Roles" />

    <div class="flex items-center justify-between my-6">
      <div>
        <Link :href="route('settings.index')" class="text-sm text-muted-foreground hover:text-primary">
          &larr; Settings
        </Link>
        <h1 class="text-2xl font-bold">Customer Roles</h1>
      </div>
      <Button as-child>
        <Link :href="route('settings.customer-roles.create')">New Role</Link>
      </Button>
    </div>

    <Card class="px-6 py-2 divide-y divide-border">
      <div
          v-for="role in customer_roles.data"
          :key="role.id"
          class="flex items-center justify-between py-3"
      >
        <div>
          <p class="font-bold">{{ role.name }}</p>
          <p class="text-sm text-muted-foreground">{{ role.customers_count }} customer(s)</p>
        </div>
        <div class="flex items-center gap-2">
          <Button as-child variant="default" size="icon-sm" aria-label="Edit" title="Edit">
            <Link :href="route('settings.customer-roles.edit', role.id)"><Pencil /></Link>
          </Button>
          <Button
              aria-label="Delete"
              title="Delete"
              size="icon-sm"
              variant="ghost"
              class="text-destructive hover:text-destructive"
              @click="destroy(role)"
          >
            <Trash2 />
          </Button>
        </div>
      </div>

      <p
          v-if="customer_roles.data.length === 0"
          class="py-6 text-center text-muted-foreground"
      >
        No customer roles yet.
      </p>
    </Card>

    <Pagination :paginator="customer_roles" />
  </AppLayout>
</template>
