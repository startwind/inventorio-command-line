<?php

namespace Startwind\Inventorio\Command;

use Symfony\Component\Console\Command\Command;

abstract class InventorioCommand extends Command
{
    public const string INVENTORIO_SERVER = 'http://localhost:8000';

    private const string USER_CONFIG_DIR = '.inventorio';
    private const string USER_CONFIG_FILE = self::USER_CONFIG_DIR . '/config.yml';

    protected function getConfigFile(): string
    {
        $home = getenv("HOME");
        return $home . DIRECTORY_SEPARATOR . self::USER_CONFIG_FILE;
    }

    protected function isInitialized(): bool
    {
        $configFile = $this->getConfigFile();
        return file_exists($configFile);
    }

    protected function getServerId(): string
    {
        if (!$this->isInitialized()) {
            throw new \RuntimeException('System was not initialized yet.');
        }

        $config = json_decode(file_get_contents($this->getConfigFile()));

        return $config['serverId'];
    }
}
