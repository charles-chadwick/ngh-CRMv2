<!--
  ContactCard
  -----------
  Pure presentational display of a single contact: type badge, a "Primary"
  marker, the formatted address block, and work/mobile phone lines.
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
  <div>
    <div class="flex items-center justify-between gap-2">
      <div
          v-if="contact.type"
          class="rounded-full bg-secondary px-2.5 py-0.5 text-xs font-bold text-secondary-foreground"
      >
        {{ contact.type }}
      </div>
      <span
          v-if="contact.is_primary"
          class="text-xs font-bold text-primary"
      >
        Primary
      </span>
    </div>

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
  </div>
</template>
