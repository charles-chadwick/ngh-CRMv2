<!--suppress JSUnresolvedReference, NpmUsedModulesInstalled -->
<script setup>
import { useDate } from '@/composables/useDate'
import Avatar from "../../Components/Avatar.vue";
import AppLayout from "../../Layouts/AppLayout.vue";
import Pagination from "../../Components/Pagination.vue";
import Search from "../../Components/Search.vue";
import { Card, CardContent, CardHeader } from "@/Components/UI/card/index.js";
import SortDropdown from "@/Components/SortDropdown.vue";
import ViewToggle from "@/Components/ViewToggle.vue";
import { useViewMode } from "@/composables/useViewMode.js";
import { Button } from "@/Components/UI/button/index.js";
import { Link } from "@inertiajs/vue3";
import { Plus } from "lucide-vue-next";

const { diffForHumans, dateTimeString } = useDate ()

defineProps ( {
  users: Object,
  can_create_user: Boolean,
} )

const { view, grid_class } = useViewMode ( 'users_view' )
</script>

<template>
  <AppLayout>
    <div class="flex items-center justify-between my-6">
      <h1 class="text-2xl font-bold">Users</h1>
      <Button
          v-if="can_create_user"
          as-child
          variant="stone"
          size="icon-sm"
      >
        <Link
            :href="route('users.create')"
            aria-label="New User"
            title="New User"
        >
          <Plus />
        </Link>
      </Button>
    </div>
    <div class="mb-4 flex flex-col gap-4 px-4 sm:flex-row sm:items-center">
      <Search
          :route="route('users.index')"
          placeholder="Search users..."
      />
      <div class="flex items-center gap-2">
        <SortDropdown
            :options="[
                { label: 'First Name', value: 'first_name' },
                { label: 'Last Name', value: 'last_name' },
                { label: 'Email', value: 'email' },
                { label: 'Joined', value: 'created_at' },
            ]"
            :route="route('users.index')"
        />
        <ViewToggle v-model="view" />
      </div>
    </div>
    <div
        :class="grid_class"
        class="grid gap-4"
    >
      <Card
          v-for="user in users.data"
          :key="user.id"
      >
        <CardHeader class="border-b border-b-primary [.border-b]:mx-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <Avatar
                  :name="user.full_name"
                  :src="user.avatar_url"
                  size="sm"
              />
              <a
                  :href="route('users.show', user.id)"
                  class="hover:text-primary"
              >
                <div class="font-bold">{{ user.full_name }}</div>
                <div class="text-sm text-muted-foreground">{{ user.role }}</div>
              </a>
            </div>

          </div>
        </CardHeader>
        <CardContent class="text-sm">
          <div
              :title="user?.last_login?.created_at"
              class="text-muted-foreground"
          >
            <p class="text-muted-foreground">Created: {{ dateTimeString ( user.created_at ) }}</p>
          </div>
          <p class="text-muted-foreground" >Last Login:
             {{ diffForHumans ( user?.last_login?.created_at ) }}
          </p>
          <p
              v-if="user.last_login?.ip_address"
              class="text-muted-foreground"
          >
            IP Address: {{ user.last_login.ip_address }}
          </p>
        </CardContent>
      </Card>
    </div>
    <Pagination :paginator="users" />
  </AppLayout>
</template>
