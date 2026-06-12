<script setup>
import { SelectSeparator } from "reka-ui";
import { cn } from "@/lib/utils";

const props = defineProps({
  class: {
    type: [Boolean, null, String, Object, Array],
    required: false,
    skipCheck: true,
  },
});
</script>

<template>
  <SelectSeparator
    data-slot="select-separator"
    :class="cn('bg-border pointer-events-none -mx-1 my-1 h-px', props.class)"
  />
</template>
