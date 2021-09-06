<?php

namespace Cego;

class RotatingFilebeatLoggerFactory
{
    /**
     * @param array $config
     *
     * @return RotatingFilebeatLogger
     */
    public function __invoke(array $config): RotatingFilebeatLogger
    {
        return RotatingFilebeatLogger::createLogger($config['channel'] ?? 'missing channel name', $config['stream'] ?? 'php://stdout');
    }
}
