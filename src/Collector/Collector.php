<?php

namespace Startwind\Inventorio\Collector;

interface Collector
{
    public function getIdentifier(): string;

    public function collect(): array;
}
