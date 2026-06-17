<script setup>
import Avatar from "../../../Components/Avatar.vue";
import { Link } from "@inertiajs/vue3";

defineProps ( { customers: Object } )
</script>

<template>
  <div class="grid grid-cols-1 gap-4">

    <div
        v-for="customer in customers"
        :key="customer.id"
        class="text-muted-foreground"
    >

      <div class="flex flex-row justify-start items-center">
        <Link
            :href="route('customers.show', customer.id)"
        >
          <Avatar
              :name="customer.full_name"
              :src="customer.avatar_url"
              size="sm"
          />
        </Link>
        <div class="ml-6  text-stone-800">
          <p class="font-bold">
            <Link
                :href="route('customers.show', customer.id)"
                class="hover:text-primary"
            >
              {{ customer.first_name }} {{ customer.last_name }}
            </Link>
          </p>
          <p class="text-sm">{{ customer.role }}</p>
          <p class="text-sm">{{ customer.email }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>