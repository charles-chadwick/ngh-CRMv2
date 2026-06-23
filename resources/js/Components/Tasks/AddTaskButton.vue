<script setup>
import { Button } from "@/Components/UI/button/index.js";
import { Plus } from "lucide-vue-next"

defineEmits(['click'])
</script>

<template>
  <Button
      variant="stone"
      size="icon-sm"
      aria-label="Add Task"
      title="Add Task"
      @click="$emit('click')"
  >
    <Plus />
  </Button>
</template>
