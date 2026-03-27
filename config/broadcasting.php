<?php

return [
    'default' => env('BROADCAST_CONNECTION', 'redis'),

    'connections' => [
        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],
    ],

    'channel' => env('BROADCAST_CHANNEL', 'distribution'),

    'socket_io' => [
        'host' => env('SOCKET_IO_HOST', '127.0.0.1'),
        'port' => env('SOCKET_IO_PORT', 6001),
    ],
];
