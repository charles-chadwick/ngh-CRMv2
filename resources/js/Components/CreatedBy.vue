<script setup>
import { computed } from 'vue'
import { useDate } from '@/Composables/useDate.js'

const props = defineProps({
  // The user who created the record (activity log causer). May be null for
  // records created before activity logging was enabled.
  user: { type: Object, default: null },
  date: { type: String, default: null },
})

const { diffForHumans, dateTimeString } = useDate()

const creator_name = computed(() => props.user?.full_name ?? 'Unknown')
</script>

<template>
  <p class="text-xs text-muted-foreground">
    Created by {{ creator_name }}
    <span
        v-if="date"
        :title="dateTimeString(date)"
    >
      · {{ diffForHumans(date) }}
    </span>
  </p>
</template>
