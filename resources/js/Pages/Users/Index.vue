<!--suppress JSUnresolvedReference, NpmUsedModulesInstalled -->
<script setup>
import { useDate } from '@/Composables/useDate'
import Avatar from "../../Components/Avatar.vue";
import Pagination from "../../Components/Pagination.vue";
import AppLayout from "../../Layouts/AppLayout.vue";
import Search from "../../Components/Search.vue";
import { Card } from "@/Components/UI/card/index.js";

const { diffForHumans, dateTimeString } = useDate ()

const props = defineProps ( {
  users: Object
} )

</script>

<template>
  <AppLayout>
    <h1 class="text-2xl font-bold my-6">Users</h1>
    <Card class="px-6">
      <Search :route="route('users.index')" placeholder="Search users..." />

      <table class="w-full">
      <thead>
      <tr class="text-left text-muted-foreground">
        <th class="p-2"></th>
        <th class="p-2">First Name</th>
        <th class="p-2">Last Name</th>
        <th class="p-2">Email</th>
        <th class="p-2">Role</th>
        <th class="p-2">Created</th>
        <th class="p-2">Last Login</th>
        <th class="p-2">IP Address</th>
      </tr>
      </thead>
      <tbody>
      <tr
          v-for="user in users.data"
          :key="user.id"
          class="border-b border-border odd:bg-muted text-sm text-foreground"
      >
        <td class="p-2">
          <a :href="route('users.show', user.id)">
          <Avatar
              :name="`${user.first_name} ${user.last_name}`"
              :src="user.avatar_url"
              size="sm"
          />
          </a>
        </td>
        <td class="p-2">{{ user.first_name }}</td>
        <td class="p-2">{{ user.last_name }}</td>
        <td class="p-2">{{ user.email }}</td>
        <td class="p-2">{{ user.role }}</td>
        <td class="p-2">{{ dateTimeString(user.created_at) }}</td>
        <td class="p-2">
            <span :title="user?.last_login?.created_at">
            {{ diffForHumans ( user?.last_login?.created_at ) }}
          </span></td>
        <td class="p-2">{{ user.last_login?.ip_address }}</td>
      </tr>
      </tbody>
    </table>
    </Card>
    <Pagination :paginator="users" />
  </AppLayout>
</template>

<style scoped>

</style>