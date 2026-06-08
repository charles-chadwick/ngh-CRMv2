<script setup>
import { useDate } from '@/composables/useDate'
import Pagination from "../Components/Pagination.vue";

const { diffForHumans } = useDate ()

const props = defineProps ( {
  users: Object
} )

</script>

<template>
  <nav class="navbar navbar-expand-lg bg-stone-800 py-6">
    <div class="mx-auto max-w-2/3 flex flex-row items-center">
      <svg
          class="w-auto h-6 text-green-400 fill-current"
          viewBox="0 0 20 20"
      >
        <path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM6.7 9.29L9 11.6l4.3-4.3 1.4 1.42L9 14.4l-3.7-3.7 1.4-1.42z" />
      </svg>
      <a
          class="ml-3 text-white font-bold text-2xl"
          href="#"
      >ngh-CRMv2</a>
    </div>
  </nav>
  <main class="mx-auto max-w-2/3">
    <h1 class="text-2xl font-bold my-6">Users</h1>
    <table class="w-full">
      <tbody>
      <tr
          v-for="user in users.data"
          :key="user.id"
      >
        <td class="p-2">{{ user.first_name }}</td>
        <td class="p-2">{{ user.last_name }}</td>
        <td class="p-2">{{ user.email }}</td>
        <td class="p-2">
            <span :title="user.last_login.created_at">
            {{ diffForHumans ( user.last_login.created_at ) }}
          </span></td>
        <td class="p-2">{{ user.last_login.ip_address }}</td>
      </tr>
      </tbody>
    </table>
    <Pagination :paginator="users" />
  </main>
</template>

<style scoped>

</style>