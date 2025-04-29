<?php

namespace Startwind\Inventorio\Collector\Inventorio;

use Startwind\Inventorio\Collector\Collector;
use Startwind\Inventorio\Config\Config;

/**
 * This collector returns details about this Inventorio client
 */
class InventorioCollector implements Collector
{
    protected const COLLECTION_IDENTIFIER = '_Inventorio';
    private bool $isRemoteEnabled;

    private Config $config;

    public function __construct(bool $isRemoteEnabled, Config $config)
    {
        $this->isRemoteEnabled = $isRemoteEnabled;
        $this->config = $config;
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
            ],
            'logfiles' => $this->config->getLogfiles()
        ];
    }
}
