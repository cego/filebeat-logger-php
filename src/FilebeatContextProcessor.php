<?php

namespace Cego;

use Throwable;
use UAParser\Parser;

class FilebeatContextProcessor
{
    /**
     * @param array $record
     * @return array
     */
    public function __invoke(array $record): array
    {
        return self::applyContextFields($record);
    }

    /**
     * Apply various fields to context and return new record
     * @param array $record
     * @return array
     */
    private function applyContextFields(array $record)
    {
        if (!isset($record["context"])) {
            $record["context"] = [];
        }

        $record = self::applyUrlContextFields($record);
        $record = self::applyClientContextFields($record);
        $record = self::applyPHPContextFields($record);
        return self::applyUserAgentContextFields($record);
    }

    /**
     * Apply php fields and return new record
     * @param array $record
     * @return array
     */
    public static function applyPHPContextFields(array $record)
    {
        if (!isset($record["context"]["php"])) {
            $record["context"]["php"] = [];
        }

        $record["context"]["php"]['sapi'] = PHP_SAPI;
        $record["context"]["php"]['argc'] = $_SERVER['argc'] ?? null;
        $record["context"]["php"]['argv_string'] = $_SERVER['argv'] ?? null ? implode(' ', $_SERVER['argv']) : null;
        return $record;
    }

    /**
     * Apply client fields and return new record
     * @param array $record
     * @return array
     */
    public static function applyClientContextFields(array $record)
    {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? null;

        if (!isset($ip) || $ip == null) {
            return $record;
        }

        if (!isset($record["context"]["client"])) {
            $record["context"]["client"] = [];
        }

        $record["context"]["client"]["ip"] = $ip;
        return $record;
    }

    /**
     * Apply url fields and return new record
     * @param array $record
     * @return array
     */
    public static function applyUrlContextFields(array $record)
    {
        if (!isset($_SERVER['REQUEST_URI'])) {
            return $record;
        }

        if (!isset($record["context"]["url"])) {
            $record["context"]["url"] = [];
        }

        $record["context"]["url"]['path'] = $_SERVER['REQUEST_URI'] ?? null;
        $record["context"]["url"]['method'] = $_SERVER['REQUEST_METHOD'] ?? null;
        $record["context"]["url"]['referer'] = $_SERVER['HTTP_REFERER'] ?? null;
        $record["context"]["url"]['domain'] = $_SERVER['HTTP_HOST'] ?? null;

        if (!isset($record["context"]["url"]["headers"])) {
            $record["context"]["url"]["headers"] = [];
        }

        $record["context"]["url"]["headers"]["cf-request-id"] = $_SERVER['HTTP_CF_REQUEST_ID'] ?? null;
        $record["context"]["url"]["headers"]["cf-ray"] = $_SERVER['HTTP_CF_RAY'] ?? null;
        $record["context"]["url"]["headers"]["cf-warp-tag-id"] = $_SERVER['HTTP_CF_WARP_TAG_ID'] ?? null;
        $record["context"]["url"]["headers"]["cf-visitor"] = $_SERVER['HTTP_CF_VISITOR'] ?? null;
        $record["context"]["url"]["headers"]["cf-ipcountry"] = $_SERVER['HTTP_CF_IPCOUNTRY'] ?? null;
        $record["context"]["url"]["headers"]["cf-cloudflared-proxy-tunnel-hostname"] = $_SERVER['HTTP_CF_CLOUDFLARED_PROXY_TUNNEL_HOSTNAME'] ?? null;
        $record["context"]["url"]["headers"]["x-forwarded-proto"] = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null;
        $record["context"]["url"]["headers"]["x-forwarded-for"] = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;
        $record["context"]["url"]["headers"]["x-forwarded-host"] = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? null;

        return $record;
    }

    /**
     * Apply user agent fields and return new record
     * @param array $record
     * @return array
     */
    private static function applyUserAgentContextFields(array $record)
    {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return $record;
        }

        if (!isset($record["context"]["user_agent"])) {
            $record["context"]["user_agent"] = [];
        }

        $record["context"]["user_agent"]["original"] = $_SERVER['HTTP_USER_AGENT'];

        try {
            $parser = Parser::create();
            $result = $parser->parse($_SERVER['HTTP_USER_AGENT']);
        } catch (Throwable $throwable) {
            $record['context']['user_agent']['error']['message'] = $throwable->getMessage();
            $record['context']['user_agent']['error']['stack_trace'] = $throwable->getTraceAsString();
            return $record;
        }

        if (!isset($record["context"]["user_agent"]["browser"])) {
            $record["context"]["user_agent"]["browser"] = [];
        }

        $record['context']['user_agent']['browser']['name'] = $result->ua->family;
        $record['context']['user_agent']['browser']['major'] = $result->ua->major;
        $record['context']['user_agent']['browser']['minor'] = $result->ua->minor;
        $record['context']['user_agent']['browser']['patch'] = $result->ua->patch;

        if (!isset($record["context"]["user_agent"]["os"])) {
            $record["context"]["user_agent"]["os"] = [];
        }

        $record['context']['user_agent']['os']['name'] = $result->os->family;
        $record['context']['user_agent']['os']['major'] = $result->os->major;
        $record['context']['user_agent']['os']['minor'] = $result->os->minor;
        $record['context']['user_agent']['os']['patch'] = $result->os->patch;
        $record['context']['user_agent']['os']['patch_minor'] = $result->os->patchMinor;

        $record['context']['user_agent']['device.name'] = $result->device->family;

        return $record;
    }
}
