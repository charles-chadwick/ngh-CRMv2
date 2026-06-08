<script setup>
import { useDate } from '@/composables/useDate'
import Pagination from "../Components/Pagination.vue";

const { diffForHumans } = useDate()

const props = defineProps({
  users: Object
})

</script>

<template>
<nav class="navbar navbar-expand-lg bg-stone-800 py-6">
  <div class="mx-auto max-w-2/3">
  <a class=" text-white font-bold text-2xl" href="#">ngh-CRMv2</a>
  </div>
</nav>
  <main class="mx-auto max-w-2/3">
    <table class="w-full">
      <tbody>
        <tr v-for="user in users.data" :key="user.id">
          <td class="p-2">{{ user.first_name }}</td>
          <td class="p-2">{{ user.last_name }}</td>
          <td class="p-2">{{ user.email }}</td>
          <td class="p-2">{{ diffForHumans(user.last_login.created_at) }}</td>
          <td class="p-2">{{ user.last_login.ip_address }}</td>
        </tr>
      </tbody>
    </table>
    <Pagination :paginator="users" />
</main>
</template>

<style scoped>

</style>