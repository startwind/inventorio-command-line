<?php

namespace Startwind\Inventorio\Collector;

abstract class BasicCollector implements Collector
{
    protected string $identifier = '<unknown>';

    /**
     * @inheritDoc
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }
}
