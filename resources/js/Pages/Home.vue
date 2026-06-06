<script setup>
import { useDate } from '@/composables/useDate'
import Pagination from "../Components/Pagination.vue";

const { formatDate } = useDate()

defineProps({
  users: Object
})
</script>

<template>
<nav class="navbar navbar-expand-lg bg-stone-800 p-6">
  <div class="mx-auto max-w-2/3">
  <a class=" text-white font-bold text-2xl" href="#">Vue App</a>
  </div>
</nav>
  <main class="mx-auto max-w-2/3">
  <h1 class="text-4xl font-bold mt-8">Welcome to the Vue App</h1>
    <table class="w-full">
      <tbody>
        <tr v-for="user in users.data" :key="user.id">
          <td>{{ user.first_name }}</td>
          <td>{{ user.last_name }}</td>
          <td>{{ user.email }}</td>
          <td>{{ user.last_login_at }}</td>
        </tr>
      </tbody>
    </table>
    <Pagination :paginator="users" />
</main>
</template>

<style scoped>

</style>