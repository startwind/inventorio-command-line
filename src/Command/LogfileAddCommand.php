<?php

namespace Startwind\Inventorio\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class LogfileAddCommand extends InventorioCommand
{
    protected static $defaultName = 'logfile:add';
    protected static $defaultDescription = 'Add a logfile to the remote console';

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        parent::configure();

        $this
            ->addArgument('name', InputArgument::REQUIRED, 'A descriptive name for the log file')
            ->addArgument('file', InputArgument::REQUIRED, 'The logfile (absolute path)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initConfiguration($input->getOption('configFile'));

        $file = $input->getArgument('file');

        if (!file_exists($file)) {
            $output->writeln('<error>The log file was not found.</error>');
            return Command::FAILURE;
        }

        $this->config->addLogfile($file, $input->getArgument('name'));

        $output->writeln('- Logfile successfully added');
        $output->writeln('- Running the collect command to sync with inventorio.cloud');

        $this->getApplication()->find('collect')->run(new ArrayInput([]), new NullOutput());

        return Command::SUCCESS;
    }
}
