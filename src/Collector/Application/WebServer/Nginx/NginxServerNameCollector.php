<?php

namespace Startwind\Inventorio\Collector\Application\WebServer\Nginx;

use Startwind\Inventorio\Collector\Collector;
use Startwind\Inventorio\Exec\File;
use Startwind\Inventorio\Exec\Runner;

class NginxServerNameCollector implements Collector
{
    private const CONFIG_DIRECTORY = '/etc/nginx/sites-enabled';

    public const COLLECTION_IDENTIFIER = 'NginxServerName';

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

        foreach ($configurations as $configuration) {
            $config = $this->extractServerData($configuration);
            if (!empty($config['serverName'])) {
                $result[$config['serverName']] = $config;
            }
        }

        return array_values($result);
    }

    private function extractServerData(string $configFile): array
    {
        $lines = File::getInstance()->getContents($configFile, true);

        $serverName = '';
        $documentRoot = '';
        $aliases = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if (preg_match('/^\s*(#|\/\/)/', $line)) {
                continue;
            }

            if (preg_match('/^\s*server_name\s+([^;]+);/i', $line, $match)) {
                $names = preg_split('/\s+/', trim($match[1]));
                $serverName = $names[0] ?? '';
                $aliases = array_slice($names, 1);
            }

            if (preg_match('/^\s*root\s+([^;]+);/i', $line, $match)) {
                $documentRoot = trim($match[1]);
            }
        }

        return [
            self::FIELD_SERVER_NAME => $serverName,
            self::FIELD_DOCUMENT_ROOT => $documentRoot,
            'configFile' => $configFile,
            'aliases' => $aliases
        ];
    }

    private function getAllConfigurations(string $vhostDir): array
    {
        $fileHandler = File::getInstance();
        $files = $fileHandler->scanDir($vhostDir);
        $configurations = [];

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'conf') {
                $configurations[] = $vhostDir . '/' . $file;
            }
        }

        return $configurations;
    }
}