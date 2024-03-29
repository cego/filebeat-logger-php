<?php

namespace Cego;

use Throwable;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class FilebeatContextProcessor implements ProcessorInterface
{
    private array $extras;

    public function __invoke(LogRecord $record): LogRecord
    {
        $record->extra = array_merge($record->extra, $this->extras);

        $record->extra = array_merge($record->extra, ['php' => self::phpExtras()]);

        if (isset($record->context['exception']) && $record->context['exception'] instanceof Throwable) {
            $record->extra = array_merge($record->extra, self::errorExtras($record->context['exception']));
        }

        return $record;
    }

    public function __construct(array $extras = [])
    {
        $this->extras = $extras;
    }

    public static function errorExtras(Throwable $throwable): array
    {
        $message = $throwable->getMessage();
        $message = empty($message) ? get_class($throwable) . ' thrown with empty message' : $message;

        return [
            'error' => [
                'type'        => get_class($throwable),
                'stack_trace' => $throwable->getTraceAsString(),
                // error.code is type keyword, therefore always cast to string
                'code'    => (string) $throwable->getCode(),
                'message' => $message,
            ],
            'log' => [
                'origin' => [
                    'file' => [
                        'name' => $throwable->getFile(),
                        'line' => $throwable->getLine(),
                    ],
                ],
            ],
        ];
    }

    public static function phpExtras(): array
    {
        return [
            'sapi'        => PHP_SAPI,
            'argc'        => $_SERVER['argc'] ?? null,
            'argv_string' => $_SERVER['argv'] ?? null ? implode(' ', $_SERVER['argv']) : null,
        ];
    }
}
