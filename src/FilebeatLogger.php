<?php

namespace Cego;

use Adbar\Dot;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Throwable;


/**
 * Class FilebeatLogger
 */
class FilebeatLogger extends Logger
{
    /**
     * FilebeatLogger constructor.
     * @param string $channel
     * @return FilebeatLogger
     */
    public static function createLogger(string $channel)
    {
        return new FilebeatLogger($channel);
    }

    /**
     * FilebeatLogger constructor.
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
        $message = $throwable->getMessage();
        if (empty($message)) {
            $message = get_class($throwable) . " thrown with empty message";
        }

        $dot = new Dot();

        $dot->set("error.type", get_class($throwable));
        $dot->set("error.message", $message);
        $dot->set("error.code", $throwable->getCode());
        $dot->set("error.stack_trace", $throwable->getTraceAsString());

        $dot->set("log.origin.file.name", $throwable->getFile());
        $dot->set("log.origin.file.line", $throwable->getLine());

        $this->log($level, $message, $dot->all());
    }


}
