<?php

namespace Startwind\Inventorio\Collector\Container;

use Startwind\Inventorio\Collector\Collector;
use Startwind\Inventorio\Exec\File;
use Startwind\Inventorio\Exec\Runner;

class DockerCollector implements Collector
{
    public function getIdentifier(): string
    {
        return 'ContainerDocker';
    }

    private function isDockerInstalled(): bool
    {
        return Runner::getInstance()->commandExists('docker');
    }

    private function getRunningDockerContainers(): array
    {
        $runner = new Runner();

        if (!$runner->commandExists('docker')) return [];

        $cmd = "docker ps --format '{{.ID}}|{{.Image}}|{{.Names}}|{{.Ports}}|{{.CreatedAt}}|{{.Status}}|{{.Command}}'";
        $output = Runner::getInstance()->run($cmd)->getOutput();

        $lines = explode("\n", trim($output));
        $containers = [];

        foreach ($lines as $line) {
            if (empty($line)) continue;

            [$id, $image, $name, $ports, $created, $status, $command] = explode('|', $line);
            $containers[] = [
                'id' => $id,
                'image' => $image,
                'name' => $name,
                'ports' => $ports,
                'created' => $created,
                'status' => $status,
                'command' => trim($command, '"')
            ];
        }

        return $containers;
    }

    public function collect(): array
    {
        return [
            'isDockerInstalled' => $this->isDockerInstalled(),
            'isInsideDocker' => File::getInstance()->fileExists('/.dockerenv'),
            'containers' => $this->getRunningDockerContainers(),
        ];
    }
}
