<?php

namespace Startwind\Inventorio\Command;

use RuntimeException;
use Startwind\Inventorio\Config\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;

abstract class InventorioCommand extends Command
{
    protected Config $config;

    protected function configure(): void
    {
        $this->addOption('configFile', 'c', InputOption::VALUE_OPTIONAL, 'The configuration file', __DIR__ . '/../../config/default.yml');
        $this->addOption('debug', '', InputOption::VALUE_NONE, 'Switch on debug mode');
        $this->addOption('debugFile', '', InputOption::VALUE_NONE, 'Send collection from file content');
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
            throw new RuntimeException('System was not initialized yet.');
        }

        $config = json_decode(file_get_contents($this->getConfigFile()), true);

        return $config['serverId'];
    }

    /**
     * Return true if remote is enabled
     */
    protected function isRemoteEnabled(): bool
    {
        if (!$this->isInitialized()) {
            throw new RuntimeException('System was not initialized yet.');
        }

        $config = json_decode(file_get_contents($this->getConfigFile()), true);

        if (!array_key_exists('remote', $config)) {
            return false;
        }

        return $config['remote'];
    }

    protected function isCollectEnabled(): bool
    {
        if (!$this->isInitialized()) {
            throw new RuntimeException('System was not initialized yet.');
        }

        $config = json_decode(file_get_contents($this->getConfigFile()), true);

        if (!array_key_exists('collect', $config)) {
            return false;
        }

        return $config['collect'];
    }

    protected function isSmartCareEnabled(): bool
    {
        if (!$this->isInitialized()) {
            throw new RuntimeException('System was not initialized yet.');
        }

        $config = json_decode(file_get_contents($this->getConfigFile()), true);

        if (!array_key_exists('smartCare', $config)) {
            return false;
        }

        return $config['smartCare'];
    }


    protected function areLogfilesEnabled(): bool
    {
        if (!$this->isInitialized()) {
            throw new RuntimeException('System was not initialized yet.');
        }

        $config = json_decode(file_get_contents($this->getConfigFile()), true);

        if (!array_key_exists('logfile', $config)) {
            return false;
        }

        return $config['logfile'];
    }

    protected function setRemoteEnabled(bool $isEnabled): void
    {
        if (!$this->isInitialized()) {
            throw new RuntimeException('System was not initialized yet.');
        }

        $config = json_decode(file_get_contents($this->getConfigFile()), true);

        $config['remote'] = $isEnabled;

        file_put_contents($this->getConfigFile(), json_encode($config), JSON_PRETTY_PRINT);
    }

    protected function setLogfileEnabled(bool $isEnabled): void
    {
        if (!$this->isInitialized()) {
            throw new RuntimeException('System was not initialized yet.');
        }

        $config = json_decode(file_get_contents($this->getConfigFile()), true);

        $config['logfile'] = $isEnabled;

        file_put_contents($this->getConfigFile(), json_encode($config), JSON_PRETTY_PRINT);
    }

    protected function setCollectEnabled(bool $isEnabled): void
    {
        if (!$this->isInitialized()) {
            throw new RuntimeException('System was not initialized yet.');
        }

        $config = json_decode(file_get_contents($this->getConfigFile()), true);

        $config['collect'] = $isEnabled;

        file_put_contents($this->getConfigFile(), json_encode($config), JSON_PRETTY_PRINT);
    }

    protected function setSmartCareEnabled(bool $isEnabled): void
    {
        if (!$this->isInitialized()) {
            throw new RuntimeException('System was not initialized yet.');
        }

        $config = json_decode(file_get_contents($this->getConfigFile()), true);

        $config['smartCare'] = $isEnabled;

        file_put_contents($this->getConfigFile(), json_encode($config), JSON_PRETTY_PRINT);
    }


    protected function setServerApi(string $serverApi): void
    {
        if (!$this->isInitialized()) {
            throw new RuntimeException('System was not initialized yet.');
        }

        $config = json_decode(file_get_contents($this->getConfigFile()), true);

        $config['serverApi'] = $serverApi;

        file_put_contents($this->getConfigFile(), json_encode($config), JSON_PRETTY_PRINT);
    }

    /**
     * Return the user identifier.
     */
    protected function getUserId(): string
    {
        if (!$this->isInitialized()) {
            throw new RuntimeException('System was not initialized yet.');
        }

        $config = json_decode(file_get_contents($this->getConfigFile()), true);

        return $config['userId'];
    }
}
