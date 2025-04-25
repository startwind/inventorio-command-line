<?php

namespace Startwind\Inventorio\Command;

use Startwind\Inventorio\Remote\RemoteConnect;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DaemonCommand extends InventorioCommand
{
    protected static $defaultName = 'daemon';
    protected static $defaultDescription = 'Start long running daemon';

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initConfiguration($input->getOption('configFile'));

        $lastRun = time() - 7200;

        $remoteConnect = new RemoteConnect(
            $this->config->getInventorioServer(),
            $this->getServerId(),
            $this->config->getCommands()
        );

        $remoteEnabled = $this->isRemoteEnabled();

        while (true) {
            if ($lastRun < time() - 3600) {
                $this->getApplication()->find('collect')->run($input, $output);
                $lastRun = time();
            }

            if ($remoteEnabled) {
                $remoteConnect->run();
                sleep(10);
            } else {
                // if remote is disabled it is enough to wake up every 30 mins
                sleep(1800);
            }

        }
    }
}
