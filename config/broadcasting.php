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
];
