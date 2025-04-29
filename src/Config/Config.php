<?php

namespace Startwind\Inventorio\Config;

use Symfony\Component\Yaml\Yaml;

class Config
{
    private array $configArray = [];
    private array $settingsArray = [];

    public function __construct(string $configFile)
    {
        $this->configArray = Yaml::parse(file_get_contents($configFile));

        if (file_exists($this->getConfigFile())) {
            $this->settingsArray = json_decode(file_get_contents($this->getConfigFile()), true);
        }
    }

    public function getInventorioServer(): string
    {
        return $this->configArray['inventorio']['server'];
    }

    public function getCommands(): array
    {
        if (array_key_exists('commands', $this->settingsArray)) {
            return $this->settingsArray['commands'];
        } else {
            return [];
        }
    }

    public function addCommand(string $identifier, string $command, string $name): void
    {
        $commands = $this->getCommands();
        $commands[$identifier] = ['name' => $name, 'command' => $command];

        $this->settingsArray['commands'] = $commands;

        $this->storeSettings();
    }

    public function removeCommand(string $identifier): void
    {
        $commands = $this->getCommands();

        unset($commands[$identifier]);

        $this->settingsArray['commands'] = $commands;

        $this->storeSettings();
    }

    public function getLogfiles(): array
    {
        if (array_key_exists('logfiles', $this->settingsArray)) {
            return $this->settingsArray['logfiles'];
        } else {
            return [];
        }
    }

    public function addLogfile(string $logfile, string $name): void
    {
        $logfiles = $this->getLogfiles();
        $logfiles[] = ['file' => $logfile, 'name' => $name];

        $this->settingsArray['logfiles'] = $logfiles;

        $this->storeSettings();
    }

    public function removeLogfile(string $logfile): void
    {
        $logfiles = $this->getLogfiles();

        foreach($logfiles as $key => $logfileObject) {
            if($logfile === $logfileObject['file']) {
             unset($logfiles[$key]);
            }
        }

        $this->settingsArray['logfiles'] = $logfiles;

        $this->storeSettings();
    }

    private function storeSettings(): void
    {
        file_put_contents($this->getConfigFile(), json_encode($this->settingsArray));
    }

    public function getConfigFile(): string
    {
        return getenv("HOME") . DIRECTORY_SEPARATOR . $this->configArray['inventorio']['configFile'];
    }
}
