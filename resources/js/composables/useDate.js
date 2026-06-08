import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime';

dayjs.extend(relativeTime);

export function useDate() {
    const diffForHumans = (date) => {
        if (!date) return '—'

        return dayjs(date).fromNow();
    }

    return { diffForHumans }
}
