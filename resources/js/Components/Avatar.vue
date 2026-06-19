<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
    name: {
        type: String,
        default: ''
    },
    src: {
        type: String,
        default: null
    },
    fullSrc: {
        type: String,
        default: null
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['xs', 'sm', 'md', 'lg'].includes(value)
    },
    clickable: {
        type: Boolean,
        default: false
    }
})

const size_classes = {
    xs: 'size-6 text-[10px]',
    sm: 'size-12 text-xs',
    md: 'size-24 text-sm',
    lg: 'size-48 text-lg'
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

const fullImageSrc = computed(() => props.fullSrc || props.src)

const isEnlargeable = computed(() => props.clickable && !!fullImageSrc.value)

const isOpen = ref(false)

const openModal = () => {
    if (isEnlargeable.value) {
        isOpen.value = true
    }
}

const closeModal = () => {
    isOpen.value = false
}
</script>

<template>
    <span
        class="inline-flex shrink-0 items-center justify-center overflow-hidden border-border border rounded-full font-semibold"
        :class="[
            size_classes[size],
            src ? 'bg-muted' : 'bg-muted text-muted-foreground',
            isEnlargeable ? 'cursor-pointer transition hover:opacity-90' : ''
        ]"
        :title="isEnlargeable ? 'Click to view full image' : name"
        :role="isEnlargeable ? 'button' : null"
        :tabindex="isEnlargeable ? 0 : null"
        @click="openModal"
        @keydown.enter.prevent="openModal"
        @keydown.space.prevent="openModal"
    >
        <img
            v-if="src"
            :src="src"
            :alt="name"
            class="h-full w-full object-cover"
        />
        <span v-else>{{ initials }}</span>
    </span>

    <Teleport to="body">
        <div
            v-if="isOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
            @click="closeModal"
            @keydown.esc="closeModal"
        >
            <div class="relative max-h-full max-w-full" @click.stop>
                <button
                    type="button"
                    class="absolute -top-3 -right-3 flex size-8 items-center justify-center rounded-full bg-background text-foreground shadow-md border border-border"
                    aria-label="Close"
                    @click="closeModal"
                >
                    &times;
                </button>
                <img
                    :src="fullImageSrc"
                    :alt="name"
                    class="max-h-[85vh] max-w-[85vw] rounded-lg object-contain"
                />
            </div>
        </div>
    </Teleport>
</template>
