<?php

namespace Startwind\Inventorio\Collector\System\General;

use Startwind\Inventorio\Collector\Collector;

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
        var_dump('hier');

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
            return (int)getenv("NUMBER_OF_PROCESSORS");
        } elseif ($os === 'Darwin' || $os === 'Linux') {
            return (int)shell_exec("getconf _NPROCESSORS_ONLN");
        }
        return 0;
    }

    private function getMemorySize(): string
    {
        $os = PHP_OS_FAMILY;
        if ($os === 'Windows') {
            $output = shell_exec("wmic computersystem get TotalPhysicalMemory");
            preg_match("/\d+/", $output, $matches);
            return isset($matches[0]) ? round($matches[0] / 1024 / 1024, 2) : "Nicht verfügbar";
        } elseif ($os === 'Darwin') {
            $memBytes = trim(shell_exec("sysctl -n hw.memsize"));
            return round($memBytes / 1024 / 1024, 2);
        } elseif ($os === 'Linux') {
            $meminfo = file_get_contents("/proc/meminfo");
            preg_match("/MemTotal:\s+(\d+)\skB/", $meminfo, $matches);
            return isset($matches[1]) ? round($matches[1] / 1024, 2) : "Nicht verfügbar";
        }
        return 0;
    }

    function getDiskSize(): string
    {
        $bytes = disk_total_space("/");
        return round($bytes / (1024 ** 3), 2);
    }

}
