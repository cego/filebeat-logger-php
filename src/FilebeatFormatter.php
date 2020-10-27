<?php

namespace Cego;

use DateTimeZone;
use Monolog\Formatter\FormatterInterface;
use Monolog\Logger;
use Monolog\Utils;

/**
 * Class FilebeatFormatter
 */
class FilebeatFormatter implements FormatterInterface
{
    private $logLevels = [
        Logger::DEBUG => 7,
        Logger::INFO => 6,
        Logger::NOTICE => 5,
        Logger::WARNING => 4,
        Logger::ERROR => 3,
        Logger::CRITICAL => 2,
        Logger::ALERT => 1,
        Logger::EMERGENCY => 0
    ];

    private $logLevelToStream = [
        Logger::DEBUG => 'stdout',
        Logger::INFO => 'stdout',
        Logger::NOTICE => 'stdout',
        Logger::WARNING => 'stderr',
        Logger::ERROR => 'stderr',
        Logger::CRITICAL => 'stderr',
        Logger::ALERT => 'stderr',
        Logger::EMERGENCY => 'stderr'
    ];

    /**
     * Formats logger record
     *
     * @param array $record
     *
     * @return string JSON with a trailing newline
     */
    public function format(array $record)
    {
        $elasticRecord = [];

        $utcDateTime = $record['datetime']->setTimeZone(new DateTimeZone('UTC'));

        $elasticRecord['@timestamp'] = $utcDateTime->format("Y-m-d\TH:i:s.u\Z");
        $elasticRecord['log.level'] = $record['level_name'];
        $elasticRecord['log.type'] = $record['channel'];
        $elasticRecord['message'] = $record['message'];
        $elasticRecord['log.severity'] = $this->logLevels[$record['level']];

        foreach ($record['context'] as $key => $value) {
            $elasticRecord[$key] = $value;
        }

        // php-fpm only logs to stderr, php-apache only logs to stdout
        // We have to manually attach the stream field to "cheat" elasticsearch
        $elasticRecord['stream'] = $this->logLevelToStream[$record['level']];

        return Utils::jsonEncode($elasticRecord, null, false) . "\n";
    }

    /**
     * Formats multiple logger record
     *
     * @param array $records
     *
     * @return array
     */
    public function formatBatch(array $records)
    {
        foreach ($records as $key => $record) {
            $records[$key] = $this->format($record);
        }

        return $records;
    }
}
