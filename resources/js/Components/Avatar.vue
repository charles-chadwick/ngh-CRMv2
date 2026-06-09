<script setup>
import { computed } from 'vue'

const props = defineProps({
    name: {
        type: String,
        default: ''
    },
    src: {
        type: String,
        default: null
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value)
    }
})

const size_classes = {
    sm: 'h-8 w-8 text-xs',
    md: 'h-10 w-10 text-sm',
    lg: 'h-14 w-14 text-lg'
}

const initials = computed(() => {
    return props.name
        .trim()
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('') || '?'
})
</script>

<template>
    <span
        class="inline-flex shrink-0 items-center justify-center overflow-hidden border-stone-300 border rounded-full font-semibold"
        :class="[size_classes[size], src ? 'bg-stone-100' : 'bg-stone-200 text-stone-600']"
        :title="name"
    >
        <img
            v-if="src"
            :src="src"
            :alt="name"
            class="h-full w-full object-cover"
        />
        <span v-else>{{ initials }}</span>
    </span>
</template>
