<?php

namespace Cego;

use Adbar\Dot;
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

        $dot = new Dot($record);
        $dot->set("context.php.sapi", PHP_SAPI);
        $dot->set("context.php.argc", $_SERVER['argc'] ?? null);
        $dot->set("context.php.argv", $_SERVER['argv'] ?? null ? implode(' ', $_SERVER['argv']) : null);
        return $dot->all();
    }

    /**
     * Apply client fields and return new record
     * @param array $record
     * @return array
     */
    public static function applyClientContextFields(array $record)
    {

        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? null;

        if (!isset($ip) || $ip === null) {
            return $record;
        }

        $dot = new Dot($record);
        $dot->set("context.client.ip", $ip);
        return $dot->all();
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

        $dot = new Dot($record);
        $dot->set("context.url.path", $_SERVER['REQUEST_URI'] ?? null);
        $dot->set("context.url.method", $_SERVER['REQUEST_METHOD'] ?? null);
        $dot->set("context.url.referer", $_SERVER['HTTP_REFERER'] ?? null);
        $dot->set("context.url.domain", $_SERVER['HTTP_HOST'] ?? null);

        $dot->set("context.url.headers.cf-request-id", $_SERVER['HTTP_CF_REQUEST_ID'] ?? null);
        $dot->set("context.url.headers.cf-ray", $_SERVER['HTTP_CF_RAY'] ?? null);
        $dot->set("context.url.headers.cf-warp-tag-id", $_SERVER['HTTP_CF_WARP_TAG_ID'] ?? null);
        $dot->set("context.url.headers.cf-visitor", $_SERVER['HTTP_CF_VISITOR'] ?? null);
        $dot->set("context.url.headers.cf-ipcountry", $_SERVER['HTTP_CF_IPCOUNTRY'] ?? null);
        $dot->set("context.url.headers.cf-cloudflared-proxy-tunnel-hostname", $_SERVER['HTTP_CF_CLOUDFLARED_PROXY_TUNNEL_HOSTNAME'] ?? null);
        $dot->set("context.url.headers.x-forwarded-proto", $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null);
        $dot->set("context.url.headers.x-forwarded-for", $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null);
        $dot->set("context.url.headers.x-forwarded-host", $_SERVER['HTTP_X_FORWARDED_HOST'] ?? null);

        return $dot->all();
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

        $dot = new Dot($record);
        $dot->set("context.user_agent.original", $_SERVER['HTTP_USER_AGENT']);

        try {
            $parser = Parser::create();
            $result = $parser->parse($_SERVER['HTTP_USER_AGENT']);
        } catch (Throwable $ex) {
            $dot->set('context.user_agent.error.message', $ex->getMessage());
            $dot->set('context.user_agent.error.stack_trace', $ex->getTraceAsString());
            return $dot->all();
        }

        $dot->set('context.user_agent.browser.name', $result->ua->family);
        $dot->set('context.user_agent.browser.major', $result->ua->major);
        $dot->set('context.user_agent.browser.minor', $result->ua->minor);
        $dot->set('context.user_agent.browser.patch', $result->ua->patch);

        $dot->set('context.user_agent.os.name', $result->os->family);
        $dot->set('context.user_agent.os.major', $result->os->major);
        $dot->set('context.user_agent.os.minor', $result->os->minor);
        $dot->set('context.user_agent.os.patch', $result->os->patch);
        $dot->set('context.user_agent.os.patch_minor', $result->os->patchMinor);

        $dot->set('context.user_agent.device.name', $result->device->family);
        return $dot->all();
    }

}
