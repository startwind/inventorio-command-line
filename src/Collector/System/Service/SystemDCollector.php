<?php

namespace Startwind\Inventorio\Collector\System\Service;

use Startwind\Inventorio\Collector\BasicCollector;
use Startwind\Inventorio\Exec\Runner;
use Symfony\Component\Console\Command\Command;

class SystemDCollector extends BasicCollector
{
    protected string $identifier = 'ServerServiceSystemD';

    public function collect(): array
    {
        $services = $this->getServices();
        return ['services' => $services];
    }

    private function getServices(): array
    {
        $services = [];

        $output = Runner::run("systemctl list-units --type=service --all --no-legend | awk '{sub(/^● /, \"\"); print \$1}'")->getOutput();

        if (!$output) {
            return [];
        }

        $units = explode("\n", trim($output));

        foreach ($units as $unit) {
            if (empty($unit)) {
                continue;
            }

            $loadState = trim(Runner::run("systemctl show $unit --property=LoadState --value")->getOutput());
            if ($loadState !== 'loaded') {
                continue;
            }

            $id = trim(Runner::run("systemctl show $unit --property=Id --value")->getOutput());
            $description = trim(Runner::run("systemctl show $unit --property=Description --value")->getOutput());
            $activeState = trim(Runner::run("systemctl show $unit --property=ActiveState --value")->getOutput());
            $subState = trim(Runner::run("systemctl show $unit --property=SubState --value")->getOutput());

            $services[] = [
                'Id' => $id,
                'Description' => $description,
                'ActiveState' => $activeState,
                'SubState' => $subState,
            ];
        }

        return $services;
    }

}
