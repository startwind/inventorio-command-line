<?php

namespace Startwind\Inventorio\Collector\Application\WebServer\Apache;

use Startwind\Inventorio\Collector\Collector;

class ApacheServerNameCollector implements Collector
{
    private const CONFIG_DIRECTORY = '/etc/apache2/sites-enabled';

    protected const COLLECTION_IDENTIFIER = 'ApacheServerName';

    public function getIdentifier(): string
    {
        return self::COLLECTION_IDENTIFIER;
    }

    public function collect(): array
    {
        if (!file_exists(self::CONFIG_DIRECTORY)) {
            return [];
        }

        $configurations = $this->getAllConfigurations(self::CONFIG_DIRECTORY);
        $serverNames = [];

        foreach ($configurations as $configuration) {
            $serverNames = array_merge($serverNames, $this->extractServerName($configuration));
        }

        if (count($serverNames) == 0) {
            return [];
        }

        return [
            'serverNames' => array_unique($serverNames)
        ];
    }

    private function extractServerName(string $vhostFile): array
    {
        $content = file_get_contents($vhostFile);
        $serverNames = [];

        if (preg_match('/ServerName\s+([^\s]+)/', $content, $match)) {
            $serverNames[] = $match[1];
        }

        if (preg_match_all('/ServerAlias\s+([^\n]+)/', $content, $matches)) {
            foreach ($matches[1] as $aliasLine) {
                $aliases = preg_split('/\s+/', trim($aliasLine));
                $serverNames = array_merge($serverNames, $aliases);
            }
        }

        return array_unique($serverNames);
    }

    private function getAllConfigurations(string $vhostDir): array
    {
        $configurations = [];

        foreach (glob($vhostDir . '/*.conf') as $file) {
            $configurations[] = $file;
        }

        return $configurations;
    }
}
