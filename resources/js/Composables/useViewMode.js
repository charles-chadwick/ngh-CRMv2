import { computed } from 'vue'
import { useStorage } from '@vueuse/core'

/**
 * Persisted grid/list view preference plus the matching grid column classes,
 * so listing pages don't each re-derive the layout.
 */
export function useViewMode(storage_key, default_view = 'grid') {
    const view = useStorage(storage_key, default_view)

    const grid_class = computed(() =>
        view.value === 'grid'
            ? 'grid-cols-1 sm:grid-cols-2 md:grid-cols-3'
            : 'grid-cols-1',
    )

    return { view, grid_class }
}
