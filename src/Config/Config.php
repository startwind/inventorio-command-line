<?php

namespace Startwind\Inventorio\Config;

use Symfony\Component\Yaml\Yaml;

class Config
{
    private array $configArray = [];

    public function __construct(string $configFile)
    {
        $this->configArray = Yaml::parse(file_get_contents($configFile));
    }

    public function getInventorioServer(): string
    {
        return $this->configArray['inventorio']['server'];
    }

    public function getCommands(): array
    {
        if (array_key_exists('commands', $this->configArray)) {
            return $this->configArray['commands'];
        } else {
            return [];
        }
    }

    public function getConfigFile(): string
    {
        return getenv("HOME") . DIRECTORY_SEPARATOR . $this->configArray['inventorio']['configFile'];
    }
}
