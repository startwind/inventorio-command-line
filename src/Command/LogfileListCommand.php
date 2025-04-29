<?php

namespace Startwind\Inventorio\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LogfileListCommand extends InventorioCommand
{
    protected static $defaultName = 'logfile:list';
    protected static $defaultDescription = 'List all registered commands';

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initConfiguration($input->getOption('configFile'));

        $logfiles = $this->config->getLogfiles();

        $table = new Table($output);

        $table->setHeaders(['Name', 'File']);

        foreach ($logfiles as $row) {
            $table->addRow([$row['name'], $row['file']]);
        }

        $table->render();

        return Command::SUCCESS;
    }
}
