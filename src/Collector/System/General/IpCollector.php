<?php

namespace Startwind\Inventorio\Collector\System\General;

use Startwind\Inventorio\Collector\Collector;

/**
 * This collector returns details about the operating system.
 *
 * - family: the OS family (MacOs, Linux, Windows). Please use the provided constants.
 * - version: the OS version
 *
 */
class IpCollector implements Collector
{
    protected const COLLECTION_IDENTIFIER = 'SystemIp';

    /**
     * @inheritDoc
     */
    public function getIdentifier(): string
    {
        return self::COLLECTION_IDENTIFIER;
    }

    /**
     * @inheritDoc
     */
    public function collect(): array
    {
        $ip = @file_get_contents('https://checkip.amazonaws.com');

        if (!$ip) {
            return [];
        }

        $ip = trim($ip);

        return ['ip' => filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null];
    }
}
