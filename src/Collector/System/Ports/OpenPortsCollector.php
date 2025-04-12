<?php

namespace Startwind\Inventorio\Collector\System\Ports;

use Startwind\Inventorio\Collector\Collector;

class OpenPortsCollector implements Collector
{
    private array $portMap = [];

    protected const COLLECTION_IDENTIFIER = 'SystemOpenPorts';

    public function __construct()
    {
        $this->loadPorts();
    }

    public function getIdentifier(): string
    {
        return self::COLLECTION_IDENTIFIER;
    }

    public function collect(): array
    {
        $ports = $this->getOpenPortsCLI();

        $enrichedPorts = [];

        foreach ($ports as $port) {
            $enrichedPorts[] = [
                'port' => $port,
                'tool' => $this->getTool($port)
            ];
        }

        return [
            'ports' => $enrichedPorts
        ];
    }

    private function getOpenPortsCLI(): array
    {
        $os = PHP_OS_FAMILY;
        $ports = [];

        if ($os === 'Windows') {
            $output = [];
            exec('netstat -an | findstr LISTENING', $output);
            foreach ($output as $line) {
                if (preg_match('/:(\d+)\s+LISTENING/', $line, $matches)) {
                    $ports[] = (int)$matches[1];
                }
            }
        } elseif ($os === 'Linux' || $os === 'Darwin') {
            // macOS and Linux
            $output = [];

            // Try lsof first
            exec("lsof -i -n -P | grep LISTEN", $output);
            if (empty($output)) {
                // Fallback to netstat
                exec("netstat -tuln", $output);
            }

            foreach ($output as $line) {
                if (preg_match('/:(\d+)\s/', $line, $matches)) {
                    $ports[] = (int)$matches[1];
                }
            }
        }

        return array_values(array_unique($ports));
    }

    private function loadPorts()
    {
        $csv = [];
        if (($handle = fopen(__DIR__ . '/ports.csv', "r")) !== false) {
            while (($data = fgetcsv($handle, 1000, ',', '"', '\\')) !== false) {
                $csv[$data[1]] = $data[2];
            }
            fclose($handle);
        }
        $this->portMap = $csv;
    }

    private function getTool(int $port): string
    {
        return $this->portMap[$port] ?? $port;
    }
}
