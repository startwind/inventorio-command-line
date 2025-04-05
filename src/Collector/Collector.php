<?php

namespace Startwind\Inventorio\Collector;

interface Collector
{
    /**
     * Return an unique identifier for this collector.
     */
    public function getIdentifier(): string;

    /**
     * Collect the specific data.
     */
    public function collect(): array;
}
