<?php

namespace Cego;

use DateTimeZone;
use Monolog\Level;
use Monolog\Utils;
use Monolog\LogRecord;
use Monolog\Formatter\FormatterInterface;

class FilebeatFormatter implements FormatterInterface
{
    public function format(LogRecord $record): string
    {
        $elasticRecord = [];

        $utcTime = $record->datetime->setTimezone(new DateTimeZone('UTC'));

        $elasticRecord['@timestamp'] = $utcTime->format("Y-m-d\TH:i:s.u\Z");
        $elasticRecord['log.level'] = $record->level->getName();
        $elasticRecord['message'] = $record['message'];
        $elasticRecord['log.channel'] = $record['channel'];
        $elasticRecord['log.severity'] = $record->level->toRFC5424Level();

        foreach ($record->extra as $key => $value) {
            $elasticRecord[$key] = $value;
        }

        foreach ($record->context as $key => $value) {
            $elasticRecord[$key] = $value;
        }

        unset($elasticRecord['exception']);

        // php-fpm only logs to stderr, php-apache only logs to stdout
        // We have to manually attach the stream field to "cheat" elasticsearch
        $elasticRecord['stream'] = $record->level->isHigherThan(Level::Notice) ? 'stderr' : 'stdout';

        return Utils::jsonEncode($elasticRecord) . "\n";
    }

    public function formatBatch(array $records): string
    {
        $lines = [];

        foreach ($records as $record) {
            $lines[] = $this->format($record);
        }

        return implode("\n", $lines);
    }
}
