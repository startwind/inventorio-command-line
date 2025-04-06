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
}
