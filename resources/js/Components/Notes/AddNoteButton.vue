<script setup>
import { Button } from "@/Components/UI/button/index.js";
import { Plus } from "lucide-vue-next"

defineEmits(['click'])
</script>

<template>
  <Button
      variant="stone"
      size="icon-sm"
      aria-label="Add Note"
      title="Add Note"
      @click="$emit('click')"
  >
    <Plus />
  </Button>
</template>
