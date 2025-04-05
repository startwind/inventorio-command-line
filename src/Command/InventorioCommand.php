<?php

namespace Startwind\Inventorio\Command;

use Symfony\Component\Console\Command\Command;

abstract class InventorioCommand extends Command
{
    public const string INVENTORIO_SERVER = 'http://localhost:8080';
    private const string USER_CONFIG_DIR = '.inventorio';
    private const string USER_CONFIG_FILE = self::USER_CONFIG_DIR . '/config.yml';

    /**
     * Return the path to the configuration file.
     */
    protected function getConfigFile(): string
    {
        $home = getenv("HOME");
        return $home . DIRECTORY_SEPARATOR . self::USER_CONFIG_FILE;
    }

    /**
     * Return true if the application is already initialized.
     */
    protected function isInitialized(): bool
    {
        $configFile = $this->getConfigFile();
        return file_exists($configFile);
    }

    /**
     * Return the server identifier.
     */
    protected function getServerId(): string
    {
        if (!$this->isInitialized()) {
            throw new \RuntimeException('System was not initialized yet.');
        }

        $config = json_decode(file_get_contents($this->getConfigFile()), true);

        return $config['serverId'];
    }

    /**
     * Return the user identifier.
     */
    protected function getUserId(): string
    {
        if (!$this->isInitialized()) {
            throw new \RuntimeException('System was not initialized yet.');
        }

        $config = json_decode(file_get_contents($this->getConfigFile()), true);

        return $config['userId'];
    }
}
