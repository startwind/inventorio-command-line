<?php

namespace Startwind\Inventorio\Collector\System\Ports;

use Startwind\Inventorio\Collector\BasicCollector;
use Startwind\Inventorio\Exec\Runner;

class PortsCollector extends BasicCollector
{
    private array $portMap = [];

    protected string $identifier = 'SystemOpenPorts';

    public function __construct()
    {
        $this->loadPorts();
    }

    public function collect(): array
    {
        $ports = $this->parseListeningPorts();

        $enrichedPorts = [];

        foreach ($ports as $port) {
            $enrichedPorts[$port['port']] = [
                'port' => $port['port'],
                'tool' => $this->getTool($port['port']),
                'external' => $port['external'],
                'protocol' => $port['protocol'],
            ];
        }

        return [
            'ports' => $enrichedPorts
        ];
    }

    private function loadPorts(): void
    {
        $csv = [];
        // this is a local dev file so no need to use the remote handler
        if (($handle = fopen(__DIR__ . '/ports.csv', "r")) !== false) {
            while (($data = fgetcsv($handle, 1000, ',', '"', '\\')) !== false) {
                $csv[$data[1]] = $data[2];
            }
            fclose($handle);
        }
        $this->portMap = $csv;
    }

    private function parseListeningPorts(): array
    {
        if(!Runner::getInstance()->commandExists("ss")) return [];

        $output = Runner::outputToArray(Runner::getInstance()->run("ss -tuln")->getOutput());

        $ports = [];

        foreach ($output as $line) {
            if (preg_match('/^(tcp|udp)\s+LISTEN\s+\S+\s+\S+\s+(\S+):(\d+)/', $line, $matches)) {
                $protocol = $matches[1];
                $ip = $matches[2];
                $port = $matches[3];

                $ip = trim($ip, '[]');

                $isExternal = !in_array($ip, ['127.0.0.1', '::1', 'localhost']) || $ip === '*';

                $ports[] = [
                    'protocol' => strtoupper($protocol),
                    'ip' => $ip,
                    'port' => $port,
                    'external' => $isExternal
                ];
            }
        }

        return $ports;
    }

    private function getTool(int $port): string
    {
        return $this->portMap[$port] ?? $port;
    }
}
