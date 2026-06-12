<script setup>
import AppLayout from "../../Layouts/AppLayout.vue";
import Input from "@/Components/UI/input/Input.vue";
import { useForm } from '@inertiajs/vue3'
import { Label } from "@/Components/UI/label/index.js";

const props = defineProps ( { customer: Object } )

const form = useForm ( {
  first_name: props.customer?.first_name || '',
  last_name: props.customer?.last_name || '',
  email: props.customer?.email || '',
  role: props.customer?.role || ''
} );

</script>
<template>
  <AppLayout>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
      <div>
        <Label
            for="first_name"
            class="form-label"
        >First Name</Label>
        <Input
            id="first_name"
            type="text"
            class="form-input w-full"
            placeholder="First Name"
            v-model="form.first_name"
        />
      </div>

      <div>
        <Label
            class="form-label"
            id="last_name"
        >Last Name</Label>
        <Input
            type="text"
            class="form-input w-full"
            placeholder="Last Name"
            id="last_name"
            v-model="form.last_name"
        />
      </div>


      <div>
        <Label
            class="form-label"
            id="email"
        >Email</Label>
        <Input
            type="text"
            class="form-input w-full"
            placeholder="Email"
            id="email"
            v-model="form.email"
        />
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>

</style>