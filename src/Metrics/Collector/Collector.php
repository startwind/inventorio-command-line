<?php

namespace Startwind\Inventorio\Metrics\Collector;

use Startwind\Inventorio\Exec\File;
use Startwind\Inventorio\Exec\Runner;
use Startwind\Inventorio\Exec\System;
use Startwind\Inventorio\Metrics\Collector\Metric\Metric;
use Startwind\Inventorio\Metrics\Collector\Metric\Webserver\ApacheAccessLogMetric;
use Startwind\Inventorio\Metrics\Collector\Metric\Webserver\ApacheErrorLogMetric;
use Startwind\Inventorio\Metrics\Memory\Memory;

class Collector
{
    private const MEMORY_KEY = 'net_traffic_history';

    private array $metrics = [];

    public function __construct()
    {
        $this->metrics[] = new ApacheAccessLogMetric();
        $this->metrics[] = new ApacheErrorLogMetric();
    }

    public function collect(): array
    {
        $runner = Runner::getInstance();
        $system = System::getInstance();

        $totalMem = (int)$runner->run("grep MemTotal /proc/meminfo | awk '{print $2}'")->getOutput() + 1;
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

        $metricResults = [
            'memory-usage' => $usedMemPercent,
            'cpu-usage' => $cpuUsagePercent,
            'disk-usage' => $diskUsedPercent,
            'network-throughput-eth0' => $this->calculateNetworkThroughput('eth0')
        ];

        foreach ($this->metrics as $metric) {
            /** @var Metric $metric */
            if ($metric->isApplicable()) {
                $lastValue = Memory::getInstance()->getLastData($metric->getName());
                $currentValue = $metric->getValue($lastValue);
                Memory::getInstance()->addData($metric->getName(), $currentValue);
                $metricResults[$metric->getName()] = $currentValue;
            }
        }

        return $metricResults;
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

        if ($total < $lastTotal || $lastTotal === 0) {
            $memory->addData(self::MEMORY_KEY, $total);
            return $total;
        }

        $throughput = $total - $lastTotal;

        $memory->addData(self::MEMORY_KEY, $total);

        return $throughput;
    }
}
