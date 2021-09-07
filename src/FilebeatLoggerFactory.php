<?php

namespace Cego;

class FilebeatLoggerFactory
{
    /**
     * @param array $config
     *
     * @return FilebeatLogger
     */
    public function __invoke(array $config): FilebeatLogger
    {
        $channel = $config['channel'] ?? 'missing channel name';

        return ($config['rotating'] ?? false)
            ? FilebeatLogger::createLogger($channel, $config['stream'] ?? 'php://stdout')
            : RotatingFilebeatLogger::createLogger($channel, $config['stream'] ?? '/storage/logs/laravel.log');
    }
}
