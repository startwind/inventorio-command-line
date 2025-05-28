<?php

namespace Startwind\Inventorio\Collector\Network;

use Startwind\Inventorio\Collector\BasicCollector;
use Startwind\Inventorio\Exec\File;
use Startwind\Inventorio\Metrics\Memory\Memory;

class NetworkTrafficCollector extends BasicCollector
{
    protected string $identifier = "NetworkThroughput";

    private const MEMORY_KEY = 'net_traffic_history';

    public function collect(): array
    {
        return [
            'eth0' => $this->calculateNetworkThroughput('eth0')
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
        $lastTotal = end($history);

        if (!empty($history) && $total < $lastTotal) {
            $memory->addData(self::MEMORY_KEY, $history);
            return 0;
        }

        if (!empty($history)) {
            $delta = $total - $lastTotal;
            $mb = round($delta / 1024 / 1024, 2);
        } else {
            $mb = 0;
        }

        $memory->addData(self::MEMORY_KEY, $total);

        return $mb;
    }
}