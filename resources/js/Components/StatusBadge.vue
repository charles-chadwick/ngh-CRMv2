<!--
  StatusBadge
  -----------
  One pill badge for any status/type value across the app (lead status, lead
  type, note type, contact type, ...). The value is matched case-insensitively
  against STATUS_COLORS to pick a palette color; unknown values fall back to a
  neutral style, so new statuses still render (just add a mapping for color).

  Usage: <StatusBadge :status="lead.status" />

  Tailwind note: the palette classes are written as full string literals so the
  Tailwind scanner includes them in the build (do not build class names
  dynamically like `bg-${color}-100`).
-->
<script setup>
import { computed } from 'vue'

const props = defineProps({
    // The status/type label to display.
    status: { type: String, default: null },
})

// The color palette: a soft surface + readable text, with dark-mode variants.
const PALETTE = {
    blue: 'bg-blue-100 text-blue-800 dark:bg-blue-500/15 dark:text-blue-300',
    sky: 'bg-sky-100 text-sky-800 dark:bg-sky-500/15 dark:text-sky-300',
    indigo: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-500/15 dark:text-indigo-300',
    violet: 'bg-violet-100 text-violet-800 dark:bg-violet-500/15 dark:text-violet-300',
    emerald: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
    amber: 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
    rose: 'bg-rose-100 text-rose-800 dark:bg-rose-500/15 dark:text-rose-300',
    slate: 'bg-slate-100 text-slate-700 dark:bg-slate-500/15 dark:text-slate-300',
    neutral: 'bg-secondary text-secondary-foreground',
}

// Map a known status/type value (lowercased) to a palette color. Values shared
// across domains (e.g. "Email", "Other") intentionally resolve to one color.
const STATUS_COLORS = {
    // Lead status (lifecycle: fresh -> in progress -> won -> done)
    new: 'blue',
    working: 'amber',
    qualified: 'emerald',
    closed: 'slate',
    // Lead type
    web: 'sky',
    phone: 'indigo',
    event: 'rose',
    // Note type
    general: 'slate',
    call: 'indigo',
    meeting: 'violet',
    'follow up': 'amber',
    email: 'violet',
    // Contact type
    office: 'blue',
    billing: 'emerald',
    shipping: 'amber',
    mailing: 'violet',
    other: 'slate',
}

const palette_classes = computed(() => {
    const key = props.status?.toLowerCase().trim()
    return PALETTE[STATUS_COLORS[key]] ?? PALETTE.neutral
})
</script>

<template>
    <span
        v-if="status"
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold"
        :class="palette_classes"
    >
        {{ status }}
    </span>
</template>
