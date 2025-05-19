<?php

namespace Startwind\Inventorio\Collector\System\Service;

use Startwind\Inventorio\Collector\BasicCollector;
use Startwind\Inventorio\Exec\Runner;
use Symfony\Component\Console\Command\Command;

class SystemDCollector extends BasicCollector
{
    public function collect(): array
    {
        $services = $this->getServices();
        return ['services' => $services];
    }

    private function getServices(): array
    {
        $process = Runner::run("systemctl list-units --type=service --all --no-legend | awk '{print $1}'");

        if ($process->getExitCode() !== Command::SUCCESS) {
            return [];
        }

        $output = $process->getOutput();

        if (!$output) {
            return [];
        }

        $services = [];
        $units = explode("\n", trim($output));

        foreach ($units as $unit) {
            if (empty($unit)) continue;

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
