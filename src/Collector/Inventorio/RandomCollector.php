<?php

namespace Startwind\Inventorio\Collector\Inventorio;

use Startwind\Inventorio\Collector\Collector;

/**
 * This collector returns details about this Inventorio client
 */
class RandomCollector implements Collector
{
    protected const string COLLECTION_IDENTIFIER = '_InventorioRandom';

    /**
     * @inheritDoc
     */
    public function getIdentifier(): string
    {
        return self::COLLECTION_IDENTIFIER;
    }

    /**
     * @inheritDoc
     */
    public function collect(): array
    {
        return [
            'random' => [
                'level2' => rand()
            ],
            'level1' => rand()
        ];
    }
}
