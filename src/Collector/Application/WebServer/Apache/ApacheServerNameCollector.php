<?php

namespace Startwind\Inventorio\Collector\Application\WebServer\Apache;

use Startwind\Inventorio\Collector\Collector;
use Startwind\Inventorio\Exec\Runner;

class ApacheServerNameCollector implements Collector
{
    private const CONFIG_DIRECTORY = '/etc/apache2/sites-enabled';

    public const COLLECTION_IDENTIFIER = 'ApacheServerName';

    public const FIELD_DOCUMENT_ROOT = 'documentRoot';
    public const FIELD_SERVER_NAME = 'serverName';

    public function getIdentifier(): string
    {
        return self::COLLECTION_IDENTIFIER;
    }

    public function collect(): array
    {
        $runner = Runner::getInstance();

        if (!$runner->fileExists(self::CONFIG_DIRECTORY)) {
            return [];
        }

        $configurations = $this->getAllConfigurations(self::CONFIG_DIRECTORY);
        $result = [];

        if (count($configurations) == 0) {
            return [];
        }

        foreach ($configurations as $configuration) {
            $config = $this->extractServerData($configuration);
            if ($config['serverName']) {
                $result[$config['serverName']] = $config;
            }
        }

        return array_values($result);
    }

    private function extractServerData(string $vhostFile): array
    {
        $lines = file($vhostFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $serverName = '';
        $aliases = [];
        $documentRoot = '';

        foreach ($lines as $line) {
            $line = trim($line);

            if (preg_match('/^\s*(#|\/\/)/', $line)) {
                continue;
            }

            if (preg_match('/^ServerName\s+([^\s]+)/i', $line, $match)) {
                $serverName = $match[1];
            }

            if (preg_match('/^ServerAlias\s+(.+)/i', $line, $match)) {
                $aliases[] = preg_split('/\s+/', trim($match[1]));
            }

            if (preg_match('/DocumentRoot\s+(.+)/', $line, $matches)) {
                $documentRoot = trim($matches[1]);
            }
        }

        return [
            self::FIELD_SERVER_NAME => $serverName,
            self::FIELD_DOCUMENT_ROOT => $documentRoot,
            'aliases' => $aliases
        ];
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
