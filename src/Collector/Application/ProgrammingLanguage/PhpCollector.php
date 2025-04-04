<?php

namespace Startwind\Inventorio\Collector\Application\ProgrammingLanguage;

use Startwind\Inventorio\Collector\Collector;

class PhpCollector implements Collector
{
    protected const string COLLECTION_IDENTIFIER = 'PHP';

    public function getIdentifier(): string
    {
        return self::COLLECTION_IDENTIFIER;
    }

    public function collect(): array
    {
        return [];
    }
}
