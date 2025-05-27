<?php

namespace Startwind\Inventorio\Collector\Metrics;

use Startwind\Inventorio\Collector\BasicCollector;
use Startwind\Inventorio\Metrics\Memory\Memory;

class MetricThresholdCollector extends BasicCollector
{
    protected string $identifier = 'MetricsThreshold';

    private array $thresholds = [
        'cpu-usage' => 80,
        'memory-usage' => 80
    ];

    public function collect(): array
    {
        $memory = Memory::getInstance();

        $result = [];

        foreach ($this->thresholds as $metric => $threshold) {
            $result[$metric] = $memory->getNumberOfGreaterThan($metric, $threshold);
        }

        return $result;
    }
}
