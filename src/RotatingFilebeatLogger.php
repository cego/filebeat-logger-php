<?php

namespace Cego;

use Monolog\Handler\StreamHandler;

class RotatingFilebeatLogger extends FilebeatLogger
{
    /**
     * @param string $stream
     *
     * @return StreamHandler[]
     */
    protected function getFilebeatHandlers(string $stream): array
    {
        $maxFiles = 5;
        $maxFileSize = 104857600; // 100MB

        return [
            new RotatingFileHandler($stream, $maxFiles, $maxFileSize),
        ];
    }
}
