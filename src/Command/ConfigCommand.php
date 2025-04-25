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
        $this->addOption('show', null, InputOption::VALUE_REQUIRED, 'Show config');

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
            $output->writeln('<info>Remote command mode: </info>' . ($value ? 'on' : 'off'));

            $this->setRemoteEnabled($value);

            $set = true;
        }

        if (!$set) {
            $output->writeln('<error>Configuration was not changed. Please provide at least one flag.</error>');
        } else {
            $this->getApplication()->find('collect')->run(new ArrayInput([]), $output);
        }

        return Command::SUCCESS;
    }

}
