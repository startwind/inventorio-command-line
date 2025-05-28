<?php

namespace Startwind\Inventorio\Metrics\Collector;

use Startwind\Inventorio\Exec\File;
use Startwind\Inventorio\Exec\Runner;
use Startwind\Inventorio\Exec\System;
use Startwind\Inventorio\Metrics\Memory\Memory;

class Collector
{
    private const MEMORY_KEY = 'net_traffic_history';

    public function collect(): array
    {
        $runner = Runner::getInstance();
        $system = System::getInstance();

        $totalMem = (int)$runner->run("grep MemTotal /proc/meminfo | awk '{print $2}'")->getOutput();
        $freeMem = (int)$runner->run("grep MemAvailable /proc/meminfo | awk '{print $2}'")->getOutput();
        $usedMem = $totalMem - $freeMem;
        $usedMemPercent = round(($usedMem / $totalMem) * 100, 2);

        $loadAvg = (float)$system->getLoadAverage()[1];
        $cpuCores = (int)$runner->run("nproc")->getOutput();

        $cpuUsagePercent = ($cpuCores > 0)
            ? round(($loadAvg / $cpuCores) * 100, 1)
            : 0;

        $diskTotal = $system->getDiskTotalSpace('/');
        $diskFree = $system->getDiskFreeSpace('/');

        $diskUsedPercent = ($diskTotal > 0)
            ? round((($diskTotal - $diskFree) / $diskTotal) * 100, 1)
            : 0;

        return [
            'memory-usage' => $usedMemPercent,
            'cpu-usage' => $cpuUsagePercent,
            'disk-usage' => $diskUsedPercent,
            'network-throughput-eth0' => $this->calculateNetworkThroughput('eth0')
        ];
    }

    function calculateNetworkThroughput($interface): float
    {
        $memory = Memory::getInstance();

        $file = new File();

        if (!$file->fileExists('/proc/net/dev')) return 0;

        $lines = $file->getContents('/proc/net/dev', true);
        foreach ($lines as $line) {
            if (strpos($line, $interface . ':') !== false) {
                $parts = preg_split('/\s+/', trim($line));
                $rx = (int)$parts[1];
                $tx = (int)$parts[9];
                $total = $rx + $tx;
                break;
            }
        }

        if (!isset($total)) return 0;

        $history = $memory->getData(self::MEMORY_KEY) ?? [];

        if (empty($history)) {
            $lastTotal = 0;
        } else {
            $lastTotal = end($history);
        }

        if ($total < $lastTotal) {
            $memory->addData(self::MEMORY_KEY, $total);
            return $total;
        }

        $throughput = $total - $lastTotal;

        $memory->addData(self::MEMORY_KEY, $total);

        return $throughput;
    }
}
