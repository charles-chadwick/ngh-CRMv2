<!--
  CreatedBy
  ---------
  Attribution block shown on a record (customer, company, note, ...).

  Renders a "Created by <user> · <when>" line, and — when update info is
  supplied — an "Updated by <user> · <when>" line directly beneath it.

  Each line is rendered by the reusable <UserAttribution> child, which owns the
  avatar, profile link and hover card. This component's only job is deciding
  WHICH lines to show; the per-user presentation lives in UserAttribution.vue.
-->
<script setup>
import { computed } from 'vue'
import UserAttribution from '@/Components/UserAttribution.vue'

// Props come from the parent model's serialized activity log (snake_case keys),
// e.g. `:user="customer.creation_activity?.causer"`.
const props = defineProps({
  // The user who created the record (activity log causer). May be null for
  // records created before activity logging was enabled.
  user: { type: Object, default: null },
  // The parent model's created_at timestamp.
  date: { type: String, default: null },
  // The user who most recently updated the record. When provided, an
  // "Updated by" line is rendered beneath the "Created by" line.
  updater: { type: Object, default: null },
  // The parent model's updated_at timestamp.
  updated_date: { type: String, default: null },
  show_avatar: { type: Boolean, default: true },
})

// Only surface the update attribution when there is something to show and it
// is not simply a mirror of the creation (e.g. the record was never edited).
const has_update = computed(
    () =>
        (props.updater || props.updated_date) &&
        props.updated_date !== props.date,
)
</script>

<template>
  <!-- Stacks the lines vertically: "Created by" always, "Updated by" optional. -->
  <span class="flex flex-col gap-0.5 text-sm text-muted-foreground">
    <!-- Always-present creation attribution. -->
    <UserAttribution
        label="Created by"
        :user="user"
        :date="date"
        :show_avatar="show_avatar"
    />
    <!-- Update attribution: only when the record has actually been edited. -->
    <UserAttribution
        v-if="has_update"
        label="Updated by"
        :user="updater"
        :date="updated_date"
        :show_avatar="show_avatar"
    />
  </span>
</template>
