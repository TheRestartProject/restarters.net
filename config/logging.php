<?php

use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

return [

    'channels' => [
        'discourse' => [
            'driver' => 'daily',
            'path' => storage_path('logs/discourse-api.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],
    ],

];
