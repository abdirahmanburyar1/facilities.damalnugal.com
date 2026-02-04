import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;


window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,   // warehouse.psivista.com
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,    // will be 443 here
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,  // also 443
    forceTLS: true,   // because scheme = https
    enabledTransports: ['ws', 'wss'],
});
