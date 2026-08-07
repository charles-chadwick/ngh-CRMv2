<script setup>
import Avatar from "../../../Components/Avatar.vue";
import { Link, router } from "@inertiajs/vue3";
import { Button } from "@/Components/UI/button/index.js";
import { Eye, Pencil, Trash2 } from "lucide-vue-next";
import { useConfirm } from "@/composables/useConfirm.js";

defineProps ( { customers: Object } )

const confirm = useConfirm()

const destroy = async (customer) => {
  if (await confirm({
    title: `Delete ${customer.full_name}?`,
    message: 'This action cannot be undone.',
    confirmLabel: 'Delete',
    variant: 'stone-destructive',
  })) {
    router.delete(route('customers.destroy', customer.id), {
      preserveScroll: true,
    })
  }
}
</script>

<template>
  <div class="grid grid-cols-1 gap-4">

    <div
        v-for="customer in customers"
        :key="customer.id"
        class="text-muted-foreground"
    >

      <div class="flex flex-row justify-between items-center gap-4">
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
          <div class="ml-6  text-foreground">
            <p class="font-bold">
              <Link
                  :href="route('customers.show', customer.id)"
                  class="hover:text-primary"
              >
                {{ customer.first_name }} {{ customer.last_name }}
              </Link>
            </p>
            <p class="text-sm">{{ customer.role?.name }}</p>
            <p class="text-sm">{{ customer.email }}</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <Button as-child variant="stone" size="icon-sm" aria-label="View" title="View">
            <Link :href="route('customers.show', customer.id)"><Eye /></Link>
          </Button>
          <Button as-child variant="stone" size="icon-sm" aria-label="Edit" title="Edit">
            <Link :href="route('customers.edit', customer.id)"><Pencil /></Link>
          </Button>
          <Button
              aria-label="Delete"
              title="Delete"
              size="icon-sm"
              variant="stone-destructive"
              @click="destroy(customer)"
          >
            <Trash2 />
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>
