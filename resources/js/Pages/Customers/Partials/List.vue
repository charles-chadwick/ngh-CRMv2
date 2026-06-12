<script setup>
import Avatar from "../../../Components/Avatar.vue";

defineProps ( { customers: Object } )
</script>

<template>
  <div class="grid grid-cols-1 gap-4">

    <div
        v-for="customer in customers"
        :key="customer.id"
        class="text-stone-600"
    >

      <div class="flex flex-row justify-start items-center">
        <a
            :href="route('customers.edit', customer.id)"
        >
          <Avatar
              :name="customer.full_name"
              :src="customer.avatar_url"
              size="sm"
          />
        </a>
        <div class="ml-6">
          <p class="font-bold">
            <a
                :href="route('customers.edit', customer.id)"
                class="hover:text-green-500"
            >
              {{ customer.first_name }} {{ customer.last_name }}
            </a>
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