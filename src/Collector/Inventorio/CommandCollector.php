<?php

namespace Startwind\Inventorio\Collector\Inventorio;

use Startwind\Inventorio\Collector\Collector;
use Startwind\Inventorio\Config\Config;

/**
 * This collector returns details about this Inventorio client
 */
class CommandCollector implements Collector
{
    protected const COLLECTION_IDENTIFIER = '_InventorioCommands';
    private array $commands;

    public function __construct(Config $config)
    {
        $this->commands = $config->getCommands();
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
            'commands' => $this->commands
        ];
    }
}
