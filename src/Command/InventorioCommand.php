<?php

namespace Startwind\Inventorio\Command;

use Startwind\Inventorio\Config\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;

abstract class InventorioCommand extends Command
{
    private const USER_CONFIG_DIR = '.inventorio';
    private const USER_CONFIG_FILE = self::USER_CONFIG_DIR . '/config.yml';

    protected Config $config;

    protected function configure(): void
    {
        $this->addOption('configFile', 'c', InputOption::VALUE_OPTIONAL, 'The configuration file', __DIR__ . '/../../config/default.yml');
    }

    /**
     * Return the path to the configuration file.
     */
    protected function getConfigFile(): string
    {
        return $this->config->getConfigFile();
    }

    protected function initConfiguration(string $configFile): void
    {
        $this->config = new Config($configFile);
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
