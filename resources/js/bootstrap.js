import 'bootstrap';

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Echo from 'laravel-echo';
import io from 'socket.io-client';

window.io = io;

const echoPort = window.Laravel?.broadcastingPort || 6001;
const echoHost = window.Laravel?.broadcastingHost || window.location.hostname;

window.Echo = new Echo({
    broadcaster: 'socket.io',
    host: echoHost + ':' + echoPort,
    transports: ['websocket', 'polling'],
    auth: {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
    },
});
