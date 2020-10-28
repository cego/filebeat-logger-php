<?php

namespace Cego;

class FilebeatLoggerFactory
{
    /**
     * @param array $config
     *
     * @return FilebeatLogger
     */
    public function __invoke(array $config)
    {
        return FilebeatLogger::createLogger($config['channel'] ?? 'missing channel name');
    }
}
