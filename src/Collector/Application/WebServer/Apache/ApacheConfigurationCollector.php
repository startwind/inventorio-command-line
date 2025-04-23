<?php

namespace Startwind\Inventorio\Collector\Application\WebServer\Apache;

use Startwind\Inventorio\Collector\BasicCollector;

class ApacheConfigurationCollector extends BasicCollector
{
    protected string $identifier = 'ApacheModules';

    public function collect(): array
    {
        return [
            'modules' => $this->getActiveApacheModules()
        ];
    }

    private function getActiveApacheModules(): array
    {
        $output = [];
        $returnVar = 0;

        exec('apache2ctl -M 2>&1', $output, $returnVar);

        if ($returnVar !== 0) {
            return [];
        }

        $modules = [];

        foreach ($output as $line) {
            if (preg_match('/^\s*([a-z_]+)_module/', $line, $matches)) {
                $modules[] = $matches[1];
            }
        }

        return $modules;
    }

}
