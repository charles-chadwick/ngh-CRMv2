import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Echo/Pusher rely on `window`, which doesn't exist during Inertia's Vite SSR
// pass. Guard so this module is a no-op on the server and only initializes in
// the browser (the notification bell reads `window.Echo` on mount, client-side).
if (typeof window !== 'undefined') {
    window.Pusher = Pusher;

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}
