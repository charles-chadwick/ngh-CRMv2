<script setup>
import Avatar from "../../../Components/Avatar.vue";

defineProps ( { customers: Object } )
</script>

<template>
    <div class="grid grid-cols-1 gap-4">

        <div v-for="customer in customers" :key="customer.id" class="text-stone-600">

            <div class="flex flex-row justify-start items-center">
              <Avatar
                  :name="customer.full_name"
                  :src="customer.avatar_url"
                  size="sm"
              />
              <div class="ml-6">
              <p class="font-bold">{{ customer.full_name }}</p>
              <p class="text-sm">{{  customer.email }}</p>
              </div>
            </div>
         </div>
    </div>
</template>

<style scoped>

</style>