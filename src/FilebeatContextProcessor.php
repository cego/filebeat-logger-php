<?php

namespace Cego;

use Throwable;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class FilebeatContextProcessor implements ProcessorInterface
{
    /**
     * @var array<array-key, mixed>
     */
    private array $extras;

    public function __invoke(LogRecord $record): LogRecord
    {
        $record->extra = array_merge($record->extra, $this->extras);

        $record->extra = array_merge($record->extra, ['php' => self::phpExtras()]);

        $record->extra = array_merge($record->extra, self::traceExtras());

        if (isset($record->context['exception']) && $record->context['exception'] instanceof Throwable) {
            $record->extra = array_merge($record->extra, self::errorExtras($record->context['exception']));
        }

        return $record;
    }

    /**
     * @param array<array-key, mixed> $extras
     */
    public function __construct(array $extras = [])
    {
        $this->extras = $extras;
    }

    /**
     * @param Throwable $throwable
     *
     * @return array<array-key, mixed>
     */
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

    /**
     * @return array<array-key, mixed>
     */
    public static function phpExtras(): array
    {
        return [
            'sapi'        => PHP_SAPI,
            'argc'        => $_SERVER['argc'] ?? null,
            'argv_string' => $_SERVER['argv'] ?? null ? implode(' ', $_SERVER['argv']) : null,
        ];
    }

    public function traceExtras(): array
    {
        if (class_exists(\OpenTelemetry\Context\Context::class)) {
            $context ??= \OpenTelemetry\Context\Context::getCurrent();
            $spanContext = \OpenTelemetry\API\Trace\Span::fromContext($context)->getContext();

            return [
                'trace' => [
                    'id' => $spanContext->getTraceId(),
                ],
            ];
        }

        if (class_exists(\Elastic\Apm\ElasticApm::class)) {
            $traceId = \Elastic\Apm\ElasticApm::getCurrentTransaction()->getTraceId();

            return [
                'trace' => [
                    'id' => $traceId,
                ],
            ];
        }

    }
}
