<?php

namespace App;

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

class DiscourseLogHandler
{
    /**
     * Create a custom Monolog instance.
     *
     * @param  array  $config
     * @return \Monolog\Logger
     */
    public function __invoke(array $config)
    {
        $logger = new Logger('custom');
        $handler = new RotatingFileHandler(
            $config['path'],
            $config['days'],
            Logger::DEBUG,
            true,
            0775
        );

        $handler->setFilenameFormat('{filename}-{date}.log', 'Y-m-d');

        return $handler;
    }
}
