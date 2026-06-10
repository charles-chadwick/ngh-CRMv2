<script setup>

import { Head } from "@inertiajs/vue3";
</script>

<template>
  <Head>nghCRM-v2</Head>
  <nav class="navbar navbar-expand-lg bg-stone-800 py-6 border-b-2 border-b-green-500  mb-4">
    <div class="flex px-32 mx-auto ">
      <div class="flex flex-row items-center">
        <svg
            class="w-auto h-6 text-green-400 fill-current"
            viewBox="0 0 20 20"
        >
          <path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM6.7 9.29L9 11.6l4.3-4.3 1.4 1.42L9 14.4l-3.7-3.7 1.4-1.42z" />
        </svg>
        <a
            class="ml-3 text-white font-bold text-2xl hover:text-green-400"
            :href="route('home')"
        >ngh-CRMv2</a>
      </div>
      <ul class="flex flex-row items-center gap-4 ml-6 text-white font-bold">
        <li><a :href="route('users.index')" class="hover:text-green-400">Users</a></li>
        <li><a :href="route('companies.index')" class="hover:text-green-400">Companies</a></li>
      </ul>
    </div>
  </nav>
  <main class="mx-auto px-32">
    <slot />
  </main>
</template>

<style scoped>

</style>