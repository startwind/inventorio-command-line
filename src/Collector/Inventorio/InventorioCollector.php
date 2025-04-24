<?php

namespace Startwind\Inventorio\Collector\Inventorio;

use Startwind\Inventorio\Collector\Collector;

/**
 * This collector returns details about this Inventorio client
 */
class InventorioCollector implements Collector
{
    protected const COLLECTION_IDENTIFIER = '_Inventorio';
    private bool $isRemoteEnabled;

    public function __construct(bool $isRemoteEnabled = false)
    {
        $this->isRemoteEnabled = $isRemoteEnabled;
    }

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
            'client' => [
                'version' => INVENTORIO_VERSION,
                'isRemoteEnabled' => $this->isRemoteEnabled,
            ]
        ];
    }
}
