<script setup>
import { Link } from '@inertiajs/vue3'
import { Button } from "@/Components/UI/button/index.js";
import { Plus } from "lucide-vue-next"

defineProps({
  href: { type: String, required: true },
})
</script>

<template>
  <Button
      as-child
      variant="default"
      size="icon-sm"
      aria-label="Start topic"
      title="Start topic"
  >
    <Link :href="href"><Plus /></Link>
  </Button>
</template>
