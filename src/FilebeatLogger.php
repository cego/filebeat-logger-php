<?php

namespace Cego;

use Throwable;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class FilebeatLogger extends Logger
{
    /**
     * @param string $channel
     * @param string $stream
     * @param array $extras
     *
     * @return static
     */
    public static function createLogger(string $channel, string $stream = 'php://stdout', array $extras = []): FilebeatLogger
    {
        return new static($channel, $stream, $extras);
    }

    /**
     * @param string $channel
     * @param string $stream
     * @param array $extras
     */
    final public function __construct(string $channel, string $stream = 'php://stdout', array $extras = [])
    {
        $handlers = $this->getFilebeatHandlers($stream);

        foreach ($handlers as $handler) {
            $handler->setFormatter(new FilebeatFormatter());
        }

        parent::__construct($channel, $handlers);

        $this->pushProcessor(new FilebeatContextProcessor($extras));

        $this->setExceptionHandler(function (Throwable $throwable): void {
            error_log("$throwable");
        });
    }

    /**
     * @param string $stream
     *
     * @return StreamHandler[]
     */
    protected function getFilebeatHandlers(string $stream): array
    {
        return [
            new StreamHandler($stream, Logger::DEBUG),
        ];
    }

    /**
     * @param Throwable $throwable
     * @param string $level
     */
    public function throwable(Throwable $throwable, $level = 'critical'): void
    {
        $context = FilebeatContextProcessor::formatThrowable($throwable);
        $message = $context['error']['message'];

        $this->log($level, $message, $context);
    }
}
