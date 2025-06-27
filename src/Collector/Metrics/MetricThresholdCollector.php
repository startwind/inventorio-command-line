<?php

namespace Startwind\Inventorio\Collector\Metrics;

use Startwind\Inventorio\Collector\BasicCollector;
use Startwind\Inventorio\Metrics\Collector\Metric\Webserver\ApacheAccessLogMetric;
use Startwind\Inventorio\Metrics\Collector\Metric\Webserver\ApacheErrorLogMetric;
use Startwind\Inventorio\Metrics\Collector\Metric\Webserver\NginxAccessLogMetric;
use Startwind\Inventorio\Metrics\Collector\Metric\Webserver\NginxErrorLogMetric;
use Startwind\Inventorio\Metrics\Memory\Memory;

class MetricThresholdCollector extends BasicCollector
{
    const THRESHOLD_ABSOLUTE = 'absolute';
    const THRESHOLD_STANDARD_DEVIATION = 'standardDeviation';

    protected string $identifier = 'MetricsThreshold';

    private array $thresholds = [
        'cpu-usage' => [
            'type' => self::THRESHOLD_ABSOLUTE,
            'threshold' => 80
        ],
        'memory-usage' => [
            'type' => self::THRESHOLD_ABSOLUTE,
            'threshold' => 80
        ],
        ApacheErrorLogMetric::IDENTIFIER => [
            'type' => self::THRESHOLD_STANDARD_DEVIATION,
            'factor' => 3,
            'outlinerMinLimit' => 10
        ],
        ApacheAccessLogMetric::IDENTIFIER => [
            'type' => self::THRESHOLD_STANDARD_DEVIATION,
            'factor' => 3,
            'outlinerMinLimit' => 10
        ],
        NginxErrorLogMetric::IDENTIFIER => [
            'type' => self::THRESHOLD_STANDARD_DEVIATION,
            'factor' => 3,
            'outlinerMinLimit' => 10
        ],
        NginxAccessLogMetric::IDENTIFIER => [
            'type' => self::THRESHOLD_STANDARD_DEVIATION,
            'factor' => 3,
            'outlinerMinLimit' => 10
        ],
    ];

    public function collect(): array
    {
        $memory = Memory::getInstance();

        $result = [];

        foreach ($this->thresholds as $metric => $threshold) {
            switch ($threshold['type']) {
                case self::THRESHOLD_ABSOLUTE:
                    if ($memory->hasData($metric)) {
                        $result[$metric] = $memory->getNumberOfGreaterThan($metric, $threshold['threshold']);
                    }
                    break;
                case self::THRESHOLD_STANDARD_DEVIATION:
                    if ($memory->hasData($metric)) {
                        $data = $memory->getData($metric);
                        $result[$metric] = $this->countStandardDeviationOutliers($data, $threshold['factor'], $threshold['outlinerMinLimit']);
                    }
                    break;
            }
        }

        return $result;
    }

    private function countStandardDeviationOutliers(array $values, float $factor = 3.0, $outlinerMinLimit = 10): int
    {
        $n = count($values);

        // we wait until we have enough data
        if ($n < 5) {
            return 0;
        }

        $sum = array_sum($values);
        $mean = $sum / $n;

        // Calculate standard deviation
        $sumOfSquares = 0.0;
        foreach ($values as $value) {
            $sumOfSquares += pow($value - $mean, 2);
        }

        $stdDev = sqrt($sumOfSquares / $n);

        if ($stdDev == 0.0) {
            return 0;
        }

        // Count outliers
        $threshold = $factor * $stdDev;
        $outliers = 0;

        foreach ($values as $value) {
            if (abs($value - $mean) > $threshold && $value > $outlinerMinLimit) {
                $outliers++;
            }
        }

        return $outliers;
    }
}
