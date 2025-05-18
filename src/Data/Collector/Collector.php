<?php

namespace Startwind\Inventorio\Data\Collector;

class Collector
{
    public function collect(): array
    {
        $totalMem = (int) shell_exec("grep MemTotal /proc/meminfo | awk '{print $2}'");
        $freeMem = (int) shell_exec("grep MemAvailable /proc/meminfo | awk '{print $2}'");
        $usedMem = $totalMem - $freeMem;

        $loadAvg = (float) sys_getloadavg()[1];
        $cpuCores = (int) shell_exec("nproc");

        $cpuUsagePercent = ($cpuCores > 0)
            ? round(($loadAvg / $cpuCores) * 100, 1)
            : 0;

        $diskTotal = disk_total_space('/');
        $diskFree = disk_free_space('/');

        $diskUsedPercent = ($diskTotal > 0)
            ? round((($diskTotal - $diskFree) / $diskTotal) * 100, 1)
            : 0;

        return [
            'memory.usage' => $usedMem,
            'cpu.usage' => $cpuUsagePercent,
            'disk.usage' => $diskUsedPercent
        ];
    }
}