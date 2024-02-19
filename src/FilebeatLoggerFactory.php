<?php

namespace Cego;

use Throwable;
use TypeError;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\ProcessorInterface;

class FilebeatLoggerFactory
{
    public function __invoke(array $config): Logger
    {
        $channel = $config['channel'] ?? 'missing channel name';
        $extras = $config['extras'] ?? [];

        $logger = new Logger($channel);

        if ($config['rotating']) {
            $handler = new RotatingFileStreamHandler($config['stream'] ?? 'storage/logs/laravel.log', 104857600, 5);
        } elseif (isset($config['handler'])) {
            $handler = $config['handler'];
        } else {
            $handler = new StreamHandler($config['stream'] ?? 'php://stdout');
        }
        $handler->setFormatter(new FilebeatFormatter());

        $logger->setHandlers([$handler]);
        $logger->pushProcessor(new FilebeatContextProcessor($extras));

        if (isset($config['httpContextProcessor'])) {
            $processor = new $config['httpContextProcessor']();

            if ( ! $processor instanceof ProcessorInterface) {
                throw new TypeError('The httpContextProcessor must be an reference to a class implementing Monolog\Processor\ProcessorInterface');
            }

            $logger->pushProcessor($processor);
        } else {
            $logger->pushProcessor(new FilebeatHttpContextProcessor());
        }

        $logger->setExceptionHandler(function (Throwable $throwable): void {
            error_log("$throwable");
        });

        return $logger;
    }
}
