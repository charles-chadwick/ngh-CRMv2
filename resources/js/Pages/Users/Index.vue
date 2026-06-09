<!--suppress JSUnresolvedReference -->
<script setup>
import { useDate } from '@/Composables/useDate'
import Avatar from "../../Components/Avatar.vue";
import Pagination from "../../Components/Pagination.vue";
import AppLayout from "../../Layouts/AppLayout.vue";

const { diffForHumans } = useDate ()

const props = defineProps ( {
  users: Object
} )

</script>

<template>
  <AppLayout>
    <h1 class="text-2xl font-bold my-6">Users</h1>
    <table class="w-full">
      <thead>
      <tr class="text-left text-stone-600">
        <th class="p-2"></th>
        <th class="p-2">First Name</th>
        <th class="p-2">Last Name</th>
        <th class="p-2">Email</th>
        <th class="p-2">Last Login</th>
        <th class="p-2">IP Address</th>
      </tr>
      </thead>
      <tbody>
      <tr
          v-for="user in users.data"
          :key="user.id"
      >
        <td class="p-2">
          <Avatar
              :name="`${user.first_name} ${user.last_name}`"
              :src="user.avatar_url"
              size="sm"
          />
        </td>
        <td class="p-2">{{ user.first_name }}</td>
        <td class="p-2">{{ user.last_name }}</td>
        <td class="p-2">{{ user.email }}</td>
        <td class="p-2">
            <span :title="user?.last_login?.created_at">
            {{ diffForHumans ( user?.last_login?.created_at ) }}
          </span></td>
        <td class="p-2">{{ user.last_login?.ip_address }}</td>
      </tr>
      </tbody>
    </table>
    <Pagination :paginator="users" />
  </AppLayout>
</template>

<style scoped>

</style>