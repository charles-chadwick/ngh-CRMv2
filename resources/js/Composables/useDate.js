import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime';

dayjs.extend(relativeTime);

export function useDate() {
    const diffForHumans = (date) => {
        if (!date) return '—'

        return dayjs(date).fromNow();
    }

    const dateTimeString = (date) => {
        if (!date) return '—'

        return dayjs(date).format('MM/DD/YYYY HH:mm');
    }

    return { diffForHumans, dateTimeString }
}
