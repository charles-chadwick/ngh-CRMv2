<!--suppress JSUnresolvedReference -->
<script setup>
  import AppLayout from '@/Layouts/AppLayout.vue'
  import { Head, Link } from '@inertiajs/vue3'
  import Avatar from "@/Components/Avatar.vue";
  import NotesSection from "@/Components/Notes/NotesSection.vue";
  import { Button } from "@/Components/UI/button/index.js";
  import { Card } from "@/Components/UI/card/index.js";

  const props = defineProps({ customer: Object, notes: Object, note_types: Array })
</script>

<template>
  <AppLayout>
    <Head :title="customer.full_name" />

    <div class="flex items-center justify-between mb-6">
      <div class="flex flex-row gap-4">
        <Avatar
            :name="customer.full_name"
            :src="customer.avatar_url"
            :full-src="customer.avatar_full_url"
            size="md"
            clickable
        />
        <div>
          <h1 class="text-2xl font-bold">{{ customer.full_name }}</h1>
          <p class="text-muted-foreground">{{ customer.role?.name || '-' }} /  {{ customer.title || '-' }}</p>
        </div>
      </div>

      <Button
          as-child
          variant="outline"
      >
        <Link :href="route('customers.edit', customer.id)">Edit</Link>
      </Button>
    </div>

    <Card class="px-6">
      <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <dt class="text-sm text-muted-foreground">Role</dt>
          <dd>{{ customer.role?.name || '—' }}</dd>
        </div>
        <div>
          <dt class="text-sm text-muted-foreground">Title</dt>
          <dd>{{ customer.title || '—' }}</dd>
        </div>
        <div>
          <dt class="text-sm text-muted-foreground">Email</dt>
          <dd>{{ customer.email }}</dd>
        </div>
        <div>
          <dt class="text-sm text-muted-foreground">Company</dt>
          <dd>
            <Link
                v-if="customer.company"
                :href="route('companies.show', customer.company.id)"
                class="hover:text-primary"
            >
              {{ customer.company.title }}
            </Link>
            <span v-else>—</span>
          </dd>
        </div>
      </dl>
    </Card>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <NotesSection
            notable_type="customer"
            :notable_id="customer.id"
            :notes="notes"
            :types="note_types"
        />
    </div>
  </AppLayout>
</template>
