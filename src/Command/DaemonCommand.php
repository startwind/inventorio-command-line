<?php

namespace Startwind\Inventorio\Command;

use Startwind\Inventorio\Data\Collector\Collector;
use Startwind\Inventorio\Data\Reporter\InventorioCloudReporter;
use Startwind\Inventorio\Remote\RemoteConnect;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DaemonCommand extends InventorioCommand
{
    protected static $defaultName = 'daemon';
    protected static $defaultDescription = 'Start long running daemon';

    private array $intervals = [
        'default' => 60 * 60,
        'remote' => 10,
        'collect' => 5 * 60
    ];

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initConfiguration($input->getOption('configFile'));

        $lastRun = [
            'default' => time() - 7200,
            'remote' => time() - 7200,
            'collect' => time() - 7200,
        ];

        $serverId = $this->getServerId();

        $remoteConnect = new RemoteConnect(
            $this->config->getInventorioServer(),
            $serverId,
            $this->config->getCommands(),
            $this->config->getSecret()
        );

        $remoteEnabled = $this->isRemoteEnabled();
        $collectEnabled = $this->isCollectEnabled();

        if ($collectEnabled) {
            $collectReporter = new InventorioCloudReporter();
            $collectCollector = new Collector();
        }

        var_dump($collectEnabled);

        while (true) {
            if ($lastRun['default'] < time() - $this->intervals['default']) {
                $this->getApplication()->find('collect')->run($input, $output);
                $lastRun['default'] = time();
            }

            if ($remoteEnabled) {
                if ($lastRun['remote'] < time() - $this->intervals['remote']) {
                    $remoteConnect->run();
                    $lastRun['remote'] = time();
                }
            }

            if ($collectEnabled) {
                if ($lastRun['collect'] < time() - $this->intervals['collect']) {
                    $collectReporter->report($serverId, $collectCollector->collect());
                    $lastRun['collect'] = time();
                }
            }

            sleep($this->getInterval());
        }
    }

    private function getInterval(): int
    {
        if ($this->isRemoteEnabled()) {
            return $this->intervals['remote'];
        }

        if ($this->isCollectEnabled()) {
            return $this->intervals['collect'];
        }

        return $this->intervals['default'];
    }
}
