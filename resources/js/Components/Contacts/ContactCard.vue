<!--
  ContactCard
  -----------
  Pure presentational display of a single contact's details: the formatted
  address block and work/mobile phone lines. The type badge and primary marker
  live in the ContactItem header (mirroring NoteItem's body/header split).
-->
<script setup>
import { computed } from "vue";

const props = defineProps({
  contact: { type: Object, required: true },
});

const cityLine = computed(() => {
  const parts = [props.contact.city, props.contact.state].filter(Boolean).join(", ");
  return [parts, props.contact.zip].filter(Boolean).join(" ");
});

const hasAddress = computed(() =>
  Boolean(props.contact.street_address || cityLine.value),
);
</script>

<template>
  <div class="mt-2 text-sm">
    <template v-if="hasAddress">
      <p v-if="contact.street_address">{{ contact.street_address }}</p>
      <p v-if="cityLine">{{ cityLine }}</p>
    </template>
    <p
        v-if="contact.work_phone"
        class="text-muted-foreground"
    >
      Work: {{ contact.work_phone }}
    </p>
    <p
        v-if="contact.mobile_phone"
        class="text-muted-foreground"
    >
      Mobile: {{ contact.mobile_phone }}
    </p>
    <p
        v-if="!hasAddress && !contact.work_phone && !contact.mobile_phone"
        class="text-muted-foreground"
    >
      No details.
    </p>
  </div>
</template>
