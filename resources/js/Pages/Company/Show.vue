<script setup>
import AppLayout from '../../Layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { ref } from 'vue'
import Avatar from "../../Components/Avatar.vue";
import CreatedBy from "@/Components/CreatedBy.vue";
import CustomerList from "../Customers/Partials/List.vue";
import NotesSection from "@/Components/Notes/NotesSection.vue";
import AddNoteButton from "@/Components/Notes/AddNoteButton.vue";
import Pagination from "@/Components/Pagination.vue";
import { Card } from "@/Components/UI/card/index.js";
import { Button } from "@/Components/UI/button/index.js";

defineProps ( { company: Object, customers: Object, notes: Object, note_types: Array } )

const showNoteForm = ref(false)
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
              :show_avatar="false"
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
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <h2 class="heading">Customers</h2>
        <Card class="px-6">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <CustomerList :customers="customers.data" />
          </div>
        </Card>
        <Pagination :paginator="customers" />

      </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">

      <div>
        <div class="flex items-center justify-between">
          <h2 class="heading">Notes</h2>
          <AddNoteButton
              v-if="!showNoteForm"
              @click="showNoteForm = true"
          />
        </div>
        <Card class="px-6">

          <NotesSection
              v-model:show-form="showNoteForm"
              notable_type="company"
              :notable_id="company.id"
              :notes="notes"
              :types="note_types"
          />
        </Card>

        <Pagination :paginator="notes" />
      </div>
    </div>

  </AppLayout>
</template>
