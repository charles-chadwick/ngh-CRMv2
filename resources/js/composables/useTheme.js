import { computed, watchEffect } from 'vue'
import { usePreferredDark, useStorage } from '@vueuse/core'

/**
 * Shared with the pre-paint bootstrap script in resources/views/app.blade.php,
 * which reads the same key to apply the theme before the first render.
 */
export const THEME_STORAGE_KEY = 'crm-theme'

export const THEME_OPTIONS = ['light', 'dark', 'system']

// Module-level state so every caller shares one preference and one watcher.
const theme = useStorage(THEME_STORAGE_KEY, 'system')
const prefers_dark = usePreferredDark()

const is_dark = computed(() =>
    theme.value === 'system' ? prefers_dark.value : theme.value === 'dark',
)

if (typeof document !== 'undefined') {
    watchEffect(() => {
        document.documentElement.classList.toggle('dark', is_dark.value)
    })
}

/**
 * Persisted light/dark/system colour preference. The effect above mirrors the
 * resolved theme onto <html> so Tailwind's `dark` variant picks it up.
 */
export function useTheme() {
    return { theme, is_dark }
}
