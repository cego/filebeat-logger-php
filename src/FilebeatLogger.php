<?php

namespace Cego;

use Throwable;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Class FilebeatLogger
 */
class FilebeatLogger extends Logger
{
    /**
     * FilebeatLogger constructor.
     *
     * @param string $channel
     * @param string $stream
     *
     * @return static
     */
    public static function createLogger(string $channel, string $stream = 'php://stdout')
    {
        return new static($channel, $stream);
    }

    /**
     * FilebeatLogger constructor.
     *
     * @param string $channel
     * @param string $stream
     */
    final public function __construct(string $channel, string $stream = 'php://stdout')
    {
        $handlers = $this->getFilebeatHandlers($stream);

        foreach ($handlers as $handler) {
            $handler->setFormatter(new FilebeatFormatter());
        }

        parent::__construct($channel, $handlers);

        $this->pushProcessor(new FilebeatContextProcessor());

        $this->setExceptionHandler(function (Throwable $throwable): void {
            error_log("$throwable");
        });
    }

    /**
     * Returns the file handlers which should be used for the FileBeatLogger
     *
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

    public function throwable(Throwable $throwable, $level = 'critical'): void
    {
        $context = FilebeatContextProcessor::formatThrowable($throwable);
        $message = $context['error']['message'];

        $this->log($level, $message, $context);
    }
}
