<script setup>
defineProps ( { customers: Object } )
</script>

<template>
    <div class="grid grid-cols-1 gap-4">
      <table>
        <thead>
          <tr class="text-left text-stone-600">
            <th>Full Name</th>
          </tr>
        </thead>
        <tbody>
        <tr v-for="customer in customers" :key="customer.id" class="text-stone-600">
          <td>{{ customer.full_name }}</td>
        </tr>
        </tbody>
      </table>
    </div>
</template>

<style scoped>

</style>