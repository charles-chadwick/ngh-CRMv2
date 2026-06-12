<script setup>
import { SelectScrollUpButton, useForwardProps } from "reka-ui";
import { ChevronUp } from "lucide-vue-next";
import { reactiveOmit } from "@vueuse/core";
import { cn } from "@/lib/utils";

const props = defineProps({
  class: {
    type: [Boolean, null, String, Object, Array],
    required: false,
    skipCheck: true,
  },
});

const delegatedProps = reactiveOmit(props, "class");
const forwardedProps = useForwardProps(delegatedProps);
</script>

<template>
  <SelectScrollUpButton
    data-slot="select-scroll-up-button"
    v-bind="forwardedProps"
    :class="
      cn('flex cursor-default items-center justify-center py-1', props.class)
    "
  >
    <ChevronUp class="size-4" />
  </SelectScrollUpButton>
</template>
