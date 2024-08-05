<?php

namespace App;

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

class DiscourseLogger
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

        // Custom formatter for better readability
        $formatter = new LineFormatter(null, null, true, true);
        $handler->setFormatter($formatter);

        // Compress rotated log files
        $handler->setFilenameFormat('{filename}-{date}.log.gz', 'Y-m-d');

        $logger->pushHandler($handler);

        return $logger;
    }
}
