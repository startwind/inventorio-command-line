<?php

namespace Startwind\Inventorio\Collector\Application\WebServer\Apache;

use Startwind\Inventorio\Collector\BasicCollector;

class ApacheConfigurationCollector extends BasicCollector
{
    protected string $identifier = 'ApacheConfiguration';

    private string $sitesEnabledPath = '/etc/apache2/sites-enabled';

    public function collect(): array
    {
        return [
            'modules' => $this->getActiveApacheModules(),
            'nonLinkedSitesEnabled' => $this->getRealConfigFilesWithServerNames()
        ];
    }

    private function getActiveApacheModules(): array
    {
        $output = [];
        $returnVar = 0;

        exec('apache2ctl -M 2>&1', $output, $returnVar);

        if ($returnVar !== 0) {
            return [];
        }

        $modules = [];

        foreach ($output as $line) {
            if (preg_match('/^\s*([a-z_]+)_module/', $line, $matches)) {
                $modules[] = $matches[1];
            }
        }

        return $modules;
    }

    public function isApacheInstalled(): bool
    {
        $output = null;
        $returnVar = null;
        exec('which apache2ctl || which httpd', $output, $returnVar);
        return !empty($output);
    }

    private function getRealConfigFilesWithServerNames(): array
    {
        if (!$this->isApacheInstalled()) {
            return [];
        }

        if (!is_dir($this->sitesEnabledPath)) {
            return [];
        }

        $configFiles = scandir($this->sitesEnabledPath);
        $results = [];

        foreach ($configFiles as $file) {
            $filePath = $this->sitesEnabledPath . DIRECTORY_SEPARATOR . $file;

            if (is_file($filePath) && !is_link($filePath)) {
                $serverName = $this->extractServerName($filePath);
                $results[] = [
                    'file' => $file,
                    'serverName' => $serverName,
                ];
            }
        }

        return $results;
    }

    private function extractServerName(string $filePath): ?string
    {
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (preg_match('/^\s*ServerName\s+(.+)$/i', $line, $matches)) {
                return trim($matches[1]);
            }
        }
        return null;
    }
}
