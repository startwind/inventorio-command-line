<?php

namespace Startwind\Inventorio\Collector\System\General;

use Startwind\Inventorio\Collector\Collector;
use Startwind\Inventorio\Exec\Runner;
use Startwind\Inventorio\Exec\System;

class ConfigurationCollector implements Collector
{
    protected const COLLECTION_IDENTIFIER = 'SystemConfiguration';

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
        return [
            'cpu' => $this->getCpuCount(),
            'memory' => (int)($this->getMemorySize() / 1000),
            'disk' => $this->getDiskSize()
        ];
    }

    private function getCpuCount(): int
    {
        $os = PHP_OS_FAMILY;
        if ($os === 'Windows') {
            return (int)System::getInstance()->getEnvironmentVariable("NUMBER_OF_PROCESSORS");
        } elseif ($os === 'Darwin' || $os === 'Linux') {
            return (int)Runner::getInstance()->run("getconf _NPROCESSORS_ONLN")->getOutput();
        }
        return 0;
    }

    private function getMemorySize(): float
    {
        $runner = Runner::getInstance();

        $os = PHP_OS_FAMILY;
        if ($os === 'Windows') {
            $output = $runner->run("wmic computersystem get TotalPhysicalMemory")->getOutput();
            preg_match("/\d+/", $output, $matches);
            return isset($matches[0]) ? round((float)$matches[0] / 1024 / 1024, 2) : 0;
        } elseif ($os === 'Darwin') {
            $memBytes = trim($runner->run("sysctl -n hw.memsize")->getOutput());
            return round((float)$memBytes / 1024 / 1024, 2);
        } elseif ($os === 'Linux') {
            $memInfo = $runner->getFileContents("/proc/meminfo");
            preg_match("/MemTotal:\s+(\d+)\skB/", $memInfo, $matches);
            return isset($matches[1]) ? round((float)$matches[1] / 1024, 2) : 0;
        }
        return 0;
    }

    function getDiskSize(): float
    {
        $bytes = System::getInstance()->getDiskTotalSpace('/');
        return round($bytes / (1024 ** 3), 2);
    }

}
