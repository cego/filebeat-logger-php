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
     *
     * @return FilebeatLogger
     */
    public static function createLogger(string $channel)
    {
        return new FilebeatLogger($channel);
    }

    /**
     * FilebeatLogger constructor.
     *
     * @param string $channel
     */
    public function __construct(string $channel)
    {
        $handlers = [
            new StreamHandler("php://stdout", Logger::DEBUG)
        ];

        foreach ($handlers as $handler) {
            $handler->setFormatter(new FilebeatFormatter());
        }

        parent::__construct($channel, $handlers);

        $this->pushProcessor(new FilebeatContextProcessor());

        $this->setExceptionHandler(function (Throwable $throwable): void {
            error_log("$throwable");
        });
    }

    public function throwable(Throwable $throwable, $level = "critical"): void
    {
        $context = FilebeatContextProcessor::formatThrowable($throwable);
        $message = $context["error"]["message"];

        $this->log($level, $message, $context);
    }
}
