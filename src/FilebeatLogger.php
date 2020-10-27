<?php

namespace Cego;

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
     * @param string $groupName
     * @return FilebeatLogger
     */
    public static function createLogger(string $groupName)
    {
        return new FilebeatLogger($groupName);
    }

    /**
     * FilebeatLogger constructor.
     * @param string $groupName
     */
    public function __construct(string $groupName)
    {
        $handlers = [
            new StreamHandler("php://stdout", Logger::DEBUG)
        ];

        foreach ($handlers as $handler) {
            $handler->setFormatter(new FilebeatFormatter());
        }

        parent::__construct($groupName, $handlers);

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
        $context = [
            'error' => [
                'type' => get_class($throwable),
                'stack_trace' => $throwable->getTraceAsString(),
                'code' => $throwable->getCode(),
                'line' => $throwable->getLine(),
                'file' => $throwable->getFile()
            ]
        ];
        $this->log($level, $message, $context);
    }


}
