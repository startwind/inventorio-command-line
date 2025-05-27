<?php

namespace Startwind\Inventorio\Metrics\Collector;

use Startwind\Inventorio\Exec\Runner;
use Startwind\Inventorio\Exec\System;

class Collector
{
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
            'disk-usage' => $diskUsedPercent
        ];
    }
}
