<script setup>
import AppLayout from '../../Layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import Avatar from "../../Components/Avatar.vue";
import CreatedBy from "@/Components/CreatedBy.vue";
import CustomerList from "../Customers/Partials/List.vue";
import NotesSection from "@/Components/Notes/NotesSection.vue";
import { Card } from "@/Components/UI/card/index.js";
import { Button } from "@/Components/UI/button/index.js";

defineProps ( { company: Object, notes: Object, note_types: Array } )
</script>

<template>
  <AppLayout>
    <Head :title="company.title" />
    <div class="flex items-center justify-between mb-6">
      <div class="flex flex-row gap-4">
        <Avatar
            :name="company.title"
            :src="company.avatar_url"
            :full-src="company.avatar_full_url"
            size="md"
            clickable
        />
        <div>
          <h1 class="text-2xl font-bold">{{ company.title }}</h1>
          <p class="text-muted-foreground">{{ company.type?.name }}</p>
          <p
              class="text-muted-foreground"
              v-html="company.description"
          ></p>
          <CreatedBy
              :user="company.creation_activity?.causer"
              :date="company.created_at"
          />
        </div>
      </div>

      <Button
          as-child
          variant="outline"
      >
        <Link :href="route('companies.edit', company.id)">Edit</Link>
      </Button>
    </div>
    <h2 class="heading">Customers</h2>
    <Card class="px-6">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <CustomerList :customers="company.customers" />
      </div>
    </Card>

    <NotesSection
        notable_type="company"
        :notable_id="company.id"
        :notes="notes"
        :types="note_types"
    />
  </AppLayout>
</template>
