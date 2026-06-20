<script setup>
import { DialogTrigger } from "reka-ui";
</script>

<template>
  <DialogTrigger data-slot="dialog-trigger" as-child>
    <slot />
  </DialogTrigger>
</template>
