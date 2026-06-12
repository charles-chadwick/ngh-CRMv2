<script setup>
import { cn } from "@/lib/utils.js";

const props = defineProps({
  class: { type: null, required: false },
});
</script>

<template>
  <div data-slot="card-content" :class="cn('px-6', props.class)">
    <slot />
  </div>
</template>
