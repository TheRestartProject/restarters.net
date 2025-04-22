<?php

return [

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public_uploads' => [
            'driver' => 'local',
            'root'   => public_path() . '/uploads',
        ],
    ],

];
