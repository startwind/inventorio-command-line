<?php

namespace Startwind\Inventorio\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class CommandRemoveCommand extends InventorioCommand
{
    protected static $defaultName = 'command:remove';
    protected static $defaultDescription = 'Remove a command to the remote console';

    protected function configure(): void
    {
        $this->addArgument('commandId', InputArgument::REQUIRED, 'The commands ID (see command:list)');
        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initConfiguration($input->getOption('configFile'));

        $commands = $this->config->getCommands();

        $id = $input->getArgument('commandId');

        if (!array_key_exists($id, $commands)) {
            $output->writeln('The given id is not known.');
            return Command::FAILURE;
        }

        $this->config->removeCommand($id);

        $this->getApplication()->find('collect')->run(new ArrayInput([]), new NullOutput());

        $output->writeln('<info>Command successfully removed</info>');

        return Command::SUCCESS;
    }
}
