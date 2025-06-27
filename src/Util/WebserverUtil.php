<?php

namespace Startwind\Inventorio\Util;

use Startwind\Inventorio\Collector\Application\WebServer\Apache\ApacheServerNameCollector;
use Startwind\Inventorio\Collector\Application\WebServer\Nginx\NginxServerNameCollector;

abstract class WebserverUtil
{
    public static function extractDocumentRoots(array $inventory): array
    {
        $documentRoots = self::extractDocumentRootByKey($inventory, ApacheServerNameCollector::COLLECTION_IDENTIFIER);
        $documentRoots = array_merge($documentRoots, self::extractDocumentRootByKey($inventory, NginxServerNameCollector::COLLECTION_IDENTIFIER));

        return array_unique($documentRoots);
    }

    private static function extractDocumentRootByKey(array $inventory, string $key): array
    {
        if (!array_key_exists($key, $inventory)
            || !is_array($inventory[$key])
        ) return [];

        $configs = $inventory[$key];

        $documentRoots = [];

        foreach ($configs as $config) {
            $documentRoots[$config[ApacheServerNameCollector::FIELD_SERVER_NAME]] = $config[ApacheServerNameCollector::FIELD_DOCUMENT_ROOT];
        }

        return $documentRoots;
    }
}