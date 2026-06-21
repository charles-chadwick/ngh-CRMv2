<!--suppress JSUnresolvedReference -->
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { ref } from 'vue'
import Avatar from "@/Components/Avatar.vue";
import CreatedBy from "@/Components/CreatedBy.vue";
import NotesSection from "@/Components/Notes/NotesSection.vue";
import AddNoteButton from "@/Components/Notes/AddNoteButton.vue";
import AddContactButton from "@/Components/Contacts/AddContactButton.vue";
import { Button } from "@/Components/UI/button/index.js";
import { Card } from "@/Components/UI/card/index.js";
import Pagination from "@/Components/Pagination.vue";
import ContactsSection from "@/Components/Contacts/ContactsSection.vue";
import { Pencil } from "lucide-vue-next";

defineProps({ customer: Object, notes: Object, note_types: Array, contacts: Object, contact_types: Array })

const showNoteForm = ref(false)
const showContactForm = ref(false)
</script>

<template>
  <AppLayout>
    <Head :title="customer.full_name" />

    <div class="flex items-center justify-between mb-6">
      <div class="flex flex-row gap-4">
        <Avatar
            :full-src="customer.avatar_full_url"
            :name="customer.full_name"
            :src="customer.avatar_url"
            clickable
            size="md"
        />
        <div>
          <h1 class="text-2xl font-bold">{{ customer.full_name }}</h1>
          <p class="text-muted-foreground">{{ customer.role?.name || '-' }} / {{ customer.title || '-' }}</p>
          <!-- Created/updated attribution for this customer (causers from the activity log). -->
          <CreatedBy
              :show_avatar="false"
              :date="customer.created_at"
              :user="customer.creation_activity?.causer"
              :updated_date="customer.updated_at"
              :updater="customer.update_activity?.causer"
          />
        </div>
      </div>

      <Button
          as-child
          variant="default"
          size="icon-sm"
          aria-label="Edit"
          title="Edit"
      >
        <Link :href="route('customers.edit', customer.id)"><Pencil /></Link>
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
      <div class="flex flex-col">
        <div class="flex items-center justify-between">
          <h2 class="heading">Notes</h2>
          <AddNoteButton
              v-if="!showNoteForm"
              @click="showNoteForm = true"
          />
        </div>
        <Card class="px-6 flex-1 flex flex-col">
          <NotesSection
              v-model:show-form="showNoteForm"
              :notable_id="customer.id"
              :notes="notes"
              :types="note_types"
              notable_type="customer"
          />
          <Pagination :paginator="notes" class="mt-auto" />
        </Card>
      </div>
      <div class="flex flex-col">
        <div class="flex items-center justify-between">
          <h2 class="heading">Contacts</h2>
          <AddContactButton
              v-if="!showContactForm"
              @click="showContactForm = true"
          />
        </div>
        <Card class="px-6 flex-1 flex flex-col">
          <ContactsSection
              v-model:show-form="showContactForm"
              :contactable_id="customer.id"
              :contacts="contacts"
              :types="contact_types"
              contactable_type="customer"
          />
          <Pagination :paginator="contacts" class="mt-auto" />
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
