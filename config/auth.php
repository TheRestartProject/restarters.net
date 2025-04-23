<?php

return [

    'guards' => [
        'api' => [
            'driver' => 'token',
            'provider' => 'users',
            'hash' => false,
        ],
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],
];
