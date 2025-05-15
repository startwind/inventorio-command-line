<?php

namespace Startwind\Inventorio\Util;

use Startwind\Inventorio\Collector\Application\WebServer\Apache\ApacheServerNameCollector;

abstract class WebsiteUtil
{
    public static function extractDomains(array $inventory): array
    {
        if (!array_key_exists(ApacheServerNameCollector::COLLECTION_IDENTIFIER, $inventory)
            || !is_array($inventory[ApacheServerNameCollector::COLLECTION_IDENTIFIER])
        ) return [];

        $configs = $inventory[ApacheServerNameCollector::COLLECTION_IDENTIFIER];

        $domains = [];

        foreach ($configs as $config) {
            $domains[] = $config[ApacheServerNameCollector::FIELD_SERVER_NAME];
        }

        return $domains;
    }
}