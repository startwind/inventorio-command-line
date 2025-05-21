<?php

namespace Startwind\Inventorio\Metrics\Collector;

use Startwind\Inventorio\Exec\Runner;

class Collector
{
    public function collect(): array
    {
        $runner = Runner::getInstance();

        $totalMem = (int)$runner->run("grep MemTotal /proc/meminfo | awk '{print $2}'")->getOutput();
        $freeMem = (int)$runner->run("grep MemAvailable /proc/meminfo | awk '{print $2}'")->getOutput();
        $usedMem = $totalMem - $freeMem;

        $loadAvg = (float)sys_getloadavg()[1];
        $cpuCores = (int)$runner->run("nproc")->getOutput();

        $cpuUsagePercent = ($cpuCores > 0)
            ? round(($loadAvg / $cpuCores) * 100, 1)
            : 0;

        $diskTotal = disk_total_space('/');
        $diskFree = disk_free_space('/');

        $diskUsedPercent = ($diskTotal > 0)
            ? round((($diskTotal - $diskFree) / $diskTotal) * 100, 1)
            : 0;

        return [
            'memory-usage' => $usedMem,
            'cpu-usage' => $cpuUsagePercent,
            'disk-usage' => $diskUsedPercent
        ];
    }
}