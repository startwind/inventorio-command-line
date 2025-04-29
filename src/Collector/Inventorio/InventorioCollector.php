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
    private bool $areLogfileEnabled;

    private Config $config;

    public function __construct(bool $isRemoteEnabled, bool $areLogfileEnabled, Config $config)
    {
        $this->isRemoteEnabled = $isRemoteEnabled;
        $this->areLogfileEnabled = $areLogfileEnabled;
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
                'areLogfilesEnabled' => $this->areLogfileEnabled
            ],
            'logfiles' => $this->config->getLogfiles()
        ];
    }
}
