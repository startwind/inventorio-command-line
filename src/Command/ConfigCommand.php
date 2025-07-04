<?php

namespace Startwind\Inventorio\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigCommand extends InventorioCommand
{
    protected static $defaultName = 'config';
    protected static $defaultDescription = 'Set features';

    protected function configure(): void
    {
        $this->addOption('remote', null, InputOption::VALUE_REQUIRED, 'Start remote command mode');
        $this->addOption('logfile', null, InputOption::VALUE_REQUIRED, 'Start logfile mode');
        $this->addOption('metrics', null, InputOption::VALUE_REQUIRED, 'Start metrics collection mode');
        $this->addOption('serverApi', null, InputOption::VALUE_REQUIRED, 'Set the server API');
        $this->addOption('smartCare', null, InputOption::VALUE_REQUIRED, 'Start smart care mode');
        // $this->addOption('show', null, InputOption::VALUE_REQUIRED, 'Show config');

        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $set = false;

        $this->initConfiguration($input->getOption('configFile'));

        if ($input->getOption('remote')) {
            $value = $input->getOption('remote');

            if ($value == 'on' || $value == 'true' || $value === true) {
                $value = true;
            } else {
                $value = false;
            }

            $output->writeln("");
            $output->writeln('Remote command mode: <info>' . ($value ? 'on' : 'off') . '</info>');

            $this->setRemoteEnabled($value);

            $set = true;
        }

        if ($input->getOption('logfile')) {
            $value = $input->getOption('logfile');

            if ($value == 'on' || $value == 'true' || $value === true) {
                $value = true;
            } else {
                $value = false;
            }

            $output->writeln("");
            $output->writeln('Logfile mode: <info>' . ($value ? 'on' : 'off') . '</info>');

            $this->setLogfileEnabled($value);

            $set = true;
        }

        if ($input->getOption('metrics')) {
            $value = $input->getOption('metrics');

            if ($value == 'on' || $value == 'true' || $value === true) {
                $value = true;
            } else {
                $value = false;
            }

            $output->writeln("");
            $output->writeln('Metrics collection mode: <info>' . ($value ? 'on' : 'off') . '</info>');

            $this->setCollectEnabled($value);

            $set = true;
        }

        if ($input->getOption('smartCare')) {
            $value = $input->getOption('smartCare');

            if ($value == 'on' || $value == 'true' || $value === true) {
                $value = true;
            } else {
                $value = false;
            }

            $output->writeln("");
            $output->writeln('SmartCare mode: <info>' . ($value ? 'on' : 'off') . '</info>');

            $this->setSmartCareEnabled($value);

            $set = true;
        }

        if ($input->getOption('serverApi')) {
            $value = $input->getOption('serverApi');

            $output->writeln("");
            $output->writeln('Setting server API to : <info>' . $value . '</info>');

            $this->setServerApi($value);

            $set = true;
        }

        if ($set) {
            $output->writeln("");
            $this->getApplication()->find('collect')->run(new ArrayInput([]), $output);
            $output->writeln("");
            $output->writeln('If you are running inventorio via SystemD please call: <info>systemctl restart inventorio.service</info>');
        }

        return Command::SUCCESS;
    }

}
