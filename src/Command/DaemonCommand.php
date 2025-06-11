<?php

namespace Startwind\Inventorio\Command;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Startwind\Inventorio\Metrics\Collector\Collector;
use Startwind\Inventorio\Metrics\Memory\Memory;
use Startwind\Inventorio\Metrics\Reporter\InventorioCloudReporter;
use Startwind\Inventorio\Remote\RemoteConnect;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DaemonCommand extends InventorioCommand
{
    protected static $defaultName = 'daemon';
    protected static $defaultDescription = 'Start long running daemon';

    private array $intervals = [
        'default' => 60 * 60 * 1, // 1 hour
        'remote' => 10, // 10 seconds
        'smartCare' => 10, // 10 seconds
        'collect' => 5 * 60 // 5 minutes
    ];

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initConfiguration($input->getOption('configFile'));

        $isDebug = $input->getOption('debug');

        if ($isDebug) {
            $logger = new Logger('name');
            $logger->pushHandler(new StreamHandler('daemon.log', Logger::DEBUG));
        }

        $lastRun = [
            'default' => time() - (24 * 60 * 60),
            'remote' => time() - (24 * 60 * 60),
            'collect' => time() - (24 * 60 * 60),
        ];

        $serverId = $this->getServerId();
        $memory = Memory::getInstance();

        $remoteConnect = new RemoteConnect(
            $this->config->getInventorioServer(),
            $serverId,
            $this->config->getCommands(),
            $this->config->getSecret()
        );

        $remoteEnabled = $this->isRemoteEnabled();
        $collectEnabled = $this->isCollectEnabled();
        $smartCareEnabled = $this->isSmartCareEnabled();

        if ($collectEnabled) {
            $collectReporter = new InventorioCloudReporter();
            $collectCollector = new Collector();
        }

        while (true) {
            if ($lastRun['default'] <= time() - $this->intervals['default']) {
                $this->getApplication()->find('collect')->run($input, $output);
                $lastRun['default'] = time();
            }

            if ($remoteEnabled || $smartCareEnabled) {
                if ($lastRun['remote'] <= time() - $this->intervals['remote']) {
                    $result = $remoteConnect->run($remoteEnabled, $smartCareEnabled);
                    if ($isDebug && $result) {
                        $logger->debug('Running command: ' . $result);
                    }
                    $lastRun['remote'] = time();
                }
            }

            if ($collectEnabled) {
                if ($lastRun['collect'] <= time() - $this->intervals['collect']) {
                    $dataset = $collectCollector->collect();
                    $memory->addDataSet($dataset);
                    $collectReporter->report($serverId, $dataset);
                    $lastRun['collect'] = time();
                }
            }

            sleep($this->getInterval());
        }
    }

    private function getInterval(): int
    {
        if ($this->isRemoteEnabled() || $this->isSmartCareEnabled()) {
            return $this->intervals['remote'];
        }

        if ($this->isCollectEnabled()) {
            return $this->intervals['collect'];
        }

        return $this->intervals['default'];
    }
}
