<?php

namespace Startwind\Inventorio\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class LogfileRemoveCommand extends InventorioCommand
{
    protected static $defaultName = 'logfile:remove';
    protected static $defaultDescription = 'Remove a logfile to the remote console';

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        parent::configure();

        $this
            ->addArgument('file', InputArgument::REQUIRED, 'The logfile (absolute path)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initConfiguration($input->getOption('configFile'));

        $file = $input->getArgument('file');

        $this->config->removeLogfile($file);

        $output->writeln('- Logfile successfully removed');
        $output->writeln('- Running the collect command to sync with inventorio.cloud');

        $this->getApplication()->find('collect')->run(new ArrayInput([]), new NullOutput());

        return Command::SUCCESS;
    }
}
