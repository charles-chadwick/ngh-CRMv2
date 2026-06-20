<script setup>
import { Button } from "@/Components/UI/button/index.js";
import { Plus } from "lucide-vue-next"

defineEmits(['click'])
</script>

<template>
  <Button
      variant="outline"
      class="bg-primary text-primary-foreground"
      size="icon-sm"
      aria-label="Add Note"
      title="Add Note"
      @click="$emit('click')"
  >
    <Plus />
  </Button>
</template>
