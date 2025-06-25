<?php

namespace Startwind\Inventorio\Collector\Application\WebServer\Nginx;

use Startwind\Inventorio\Collector\BasicCollector;
use Startwind\Inventorio\Exec\File;
use Startwind\Inventorio\Exec\Runner;
use Symfony\Component\Console\Command\Command;

class NginxConfigurationCollector extends BasicCollector
{
    protected string $identifier = 'NginxConfiguration';

    private string $sitesEnabledPath = '/etc/nginx/sites-enabled';

    public function collect(): array
    {
        return [
            'modules' => $this->getActiveNginxModules(),
            'nonLinkedSitesEnabled' => $this->getRealConfigFilesWithServerNames()
        ];
    }

    private function getActiveNginxModules(): array
    {
        $runner = Runner::getInstance();
        $result = $runner->run('nginx -V 2>&1');

        if ($result->getExitCode() !== Command::SUCCESS) {
            return [];
        }

        $output = Runner::outputToArray($result->getOutput());

        $modules = [];

        foreach ($output as $line) {
            if (preg_match_all('/--with-(\S+)/', $line, $matches)) {
                foreach ($matches[1] as $module) {
                    $modules[] = $module;
                }
            }
        }

        return $modules;
    }

    public function isNginxInstalled(): bool
    {
        $runner = Runner::getInstance();
        return $runner->commandExists('nginx');
    }

    private function getRealConfigFilesWithServerNames(): array
    {
        if (!$this->isNginxInstalled()) {
            return [];
        }

        $fileHandler = File::getInstance();

        if (!$fileHandler->isDir($this->sitesEnabledPath)) {
            return [];
        }

        $configFiles = $fileHandler->scanDir($this->sitesEnabledPath);
        $results = [];

        foreach ($configFiles as $file) {
            $filePath = $this->sitesEnabledPath . DIRECTORY_SEPARATOR . $file;

            if ($fileHandler->isFile($filePath) && !$fileHandler->isLink($filePath)) {
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
        $lines = File::getInstance()->getContents($filePath, true);
        foreach ($lines as $line) {
            if (preg_match('/^\s*server_name\s+([^;]+);/i', $line, $matches)) {
                $names = preg_split('/\s+/', trim($matches[1]));
                return $names[0] ?? null;
            }
        }
        return null;
    }
}