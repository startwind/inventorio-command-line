<?php

namespace Startwind\Inventorio\Collector\Application\WebServer;

use Startwind\Inventorio\Collector\Collector;

class ApacheCollector implements Collector
{
    protected const string COLLECTION_IDENTIFIER = 'Apache';

    public function getIdentifier(): string
    {
        return self::COLLECTION_IDENTIFIER;
    }

    public function collect(): array
    {
        return [];
    }

}
