import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Pusher's constructor throws synchronously if the app key is missing, and
// since this file is imported before app.js calls Alpine.start(), an
// unconfigured Reverb service (no VITE_REVERB_* build-time env vars) would
// abort the whole script — silently breaking every button/dropdown on the
// page, not just chat. Skip wiring up Echo until a real key is present.
const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;

if (reverbKey) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}
