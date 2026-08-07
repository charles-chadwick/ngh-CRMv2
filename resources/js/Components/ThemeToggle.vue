<!--
  ThemeToggle
  -----------
  Segmented Light / Dark / System picker. The choice is persisted by useTheme,
  which also applies the resolved theme to the <html> element.
-->
<script setup>
import { Monitor, Moon, Sun } from 'lucide-vue-next'
import { useTheme } from '@/composables/useTheme'

const { theme } = useTheme()

const options = [
  { value: 'light', label: 'Light', icon: Sun },
  { value: 'dark', label: 'Dark', icon: Moon },
  { value: 'system', label: 'System', icon: Monitor },
]
</script>

<template>
  <div
      class="grid grid-cols-3 gap-1 rounded-md bg-muted p-1"
      role="radiogroup"
      aria-label="Colour theme"
  >
    <button
        v-for="option in options"
        :key="option.value"
        type="button"
        role="radio"
        :aria-checked="theme === option.value"
        :aria-label="option.label"
        class="flex flex-col items-center gap-1 rounded px-1 py-1.5 text-xs font-medium transition-colors"
        :class="theme === option.value
            ? 'bg-background text-foreground shadow-xs'
            : 'text-muted-foreground hover:text-foreground'"
        @click="theme = option.value"
    >
      <component :is="option.icon" class="h-4 w-4" />
      {{ option.label }}
    </button>
  </div>
</template>
