<?php

namespace Startwind\Inventorio\Metrics\Collector\Metric;

interface Metric
{
    public function getName(): string;

    public function getValue(float $lastValue): float;

    public function isApplicable(): bool;
}