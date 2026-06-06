import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime';
import { computed, ref } from "vue";

export function useDate () {
    const formatDate = ( date ) => {
        if ( ! date ) return '—'

        // Extend Day.js with the relative time plugin
        dayjs.extend ( relativeTime );

        // Your API date string (e.g., Laravel's created_at)
        const createdAt = ref ( date);

        // Computed property will dynamically update the time diff
        const timeAgo = computed ( () => {
            return dayjs ( createdAt.value ).fromNow ();
        } );
    }

    return { formatDate }
}
