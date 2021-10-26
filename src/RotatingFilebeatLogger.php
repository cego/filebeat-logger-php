<?php

namespace Cego;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;

class RotatingFilebeatLogger extends FilebeatLogger
{
    /**
     * @param string $stream
     *
     * @return StreamHandler[]
     */
    protected function getFilebeatHandlers(string $stream): array
    {
        return [
            new RotatingFileHandler($stream, 4, Logger::DEBUG),
        ];
    }
}
