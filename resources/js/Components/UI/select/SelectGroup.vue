<script setup>
import { SelectGroup } from "reka-ui";
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
  <SelectGroup data-slot="select-group" :class="cn('p-1', props.class)">
    <slot />
  </SelectGroup>
</template>
