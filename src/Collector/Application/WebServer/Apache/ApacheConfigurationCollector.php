<?php

namespace Startwind\Inventorio\Collector\Application\WebServer\Apache;

use Startwind\Inventorio\Collector\BasicCollector;
use Startwind\Inventorio\Exec\Runner;
use Symfony\Component\Console\Command\Command;
use Startwind\Inventorio\Exec\File;

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
        $runner = Runner::getInstance();
        $result = $runner->run('apache2ctl -M 2>&1');

        if ($result->getExitCode() !== Command::SUCCESS) {
            return [];
        }

        $output = Runner::outputToArray($result->getOutput());

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
        $runner = Runner::getInstance();
        return $runner->commandExists('apache2ctl') || $runner->commandExists('httpd');
    }

    private function getRealConfigFilesWithServerNames(): array
    {
        if (!$this->isApacheInstalled()) {
            return [];
        }

        if (!File::getInstance()->isDir($this->sitesEnabledPath)) {
            return [];
        }

        $configFiles = scandir($this->sitesEnabledPath);
        $results = [];

        foreach ($configFiles as $file) {
            $filePath = $this->sitesEnabledPath . DIRECTORY_SEPARATOR . $file;

            if (File::getInstance()->isFile($filePath) && !File::getInstance()->isLink($filePath)) {
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
