<?php

namespace Cego;

use Throwable;
use UAParser\Parser;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class FilebeatHttpContextProcessor implements ProcessorInterface
{
    private array $extras;

    public function __invoke(LogRecord $record): LogRecord
    {
        $record->extra = array_merge($record->extra, $this->extras);

        if (isset($_SERVER['REQUEST_METHOD'])) {
            $record->extra = array_merge($record->extra, ['http' => self::httpExtras()]);
            $record->extra = array_merge($record->extra, ['url' => self::urlExtras()]);
            $record->extra = array_merge($record->extra, ['user_agent' => self::userAgentExtras()]);
            $record->extra = array_merge($record->extra, ['client' => self::clientExtras()]);
        }

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

    public static function clientExtras(): array
    {
        return [
            'ip'      => $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? null,
            'address' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
            'geo'     => [
                'country_iso_code' => $_SERVER['HTTP_CF_IPCOUNTRY'] ?? null,
            ],
        ];
    }

    public static function httpExtras(): array
    {
        return [
            'request' => [
                'id'     => $_SERVER['HTTP_CF_RAY'] ?? null,
                'method' => $_SERVER['REQUEST_METHOD'] ?? null,
            ],
        ];
    }

    public static function urlExtras(): array
    {
        return [
            'path'    => $_SERVER['REQUEST_URI'] ?? null,
            'method'  => $_SERVER['REQUEST_METHOD'] ?? null,
            'referer' => $_SERVER['HTTP_REFERER'] ?? null,
            'domain'  => $_SERVER['HTTP_HOST'] ?? null,
        ];
    }

    private static function userAgentExtras(): array|null
    {
        $original = $_SERVER['HTTP_USER_AGENT'] ?? null;

        if ( ! isset($original)) {
            return null;
        }

        try {
            $parser = Parser::create();
            $result = $parser->parse($original);
        } catch (Throwable $throwable) {
            return [
                'original' => $original,
                'error'    => [
                    'message'     => $throwable->getMessage(),
                    'stack_trace' => $throwable->getTraceAsString(),
                ],
            ];
        }

        return [
            'original' => $original,
            'browser'  => [
                'name'  => $result->ua->family,
                'major' => $result->ua->major,
                'minor' => $result->ua->minor,
                'patch' => $result->ua->patch,
            ],
            'os' => [
                'name'        => $result->os->family,
                'major'       => $result->os->major,
                'minor'       => $result->os->minor,
                'patch'       => $result->os->patch,
                'patch_minor' => $result->os->patchMinor,
            ],
            'device.name' => $result->device->family,
        ];
    }
}
