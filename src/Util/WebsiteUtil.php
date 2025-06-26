<?php

namespace Startwind\Inventorio\Util;

use Startwind\Inventorio\Collector\Application\WebServer\Apache\ApacheServerNameCollector;
use Startwind\Inventorio\Collector\Application\WebServer\Nginx\NginxServerNameCollector;

abstract class WebsiteUtil
{
    public static function extractDomains(array $inventory): array
    {
        $domains = self::extractDomainsByKey($inventory, ApacheServerNameCollector::COLLECTION_IDENTIFIER);
        $domains = array_merge($domains, self::extractDomainsByKey($inventory, NginxServerNameCollector::COLLECTION_IDENTIFIER));

        return array_unique($domains);
    }

    private static function extractDomainsByKey(array $inventory, string $key): array
    {
        if (!array_key_exists($key, $inventory)
            || !is_array($inventory[$key])
        ) return [];

        $configs = $inventory[$key];

        $domains = [];

        foreach ($configs as $config) {
            $domains[] = $config[$key];
        }

        return $domains;
    }
}