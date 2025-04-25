<?php

namespace Startwind\Inventorio\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommandListCommand extends InventorioCommand
{
    protected static $defaultName = 'command:list';
    protected static $defaultDescription = 'List all registered commands';

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initConfiguration($input->getOption('configFile'));

        $commands = $this->config->getCommands();

        $table = new Table($output);

        $table->setHeaders(['ID', 'Name', 'Command']);

        foreach ($commands as $id => $row) {
            $table->addRow([$id, $row['name'], $row['command']]);
        }

        $table->render();

        return Command::SUCCESS;
    }
}
