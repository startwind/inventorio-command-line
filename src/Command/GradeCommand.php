<?php

namespace Startwind\Inventorio\Command;

use Exception;
use GuzzleHttp\Client;
use Startwind\Inventorio\Collector\ClientAwareCollector;
use Startwind\Inventorio\Collector\InventoryAwareCollector;
use Startwind\Inventorio\Metrics\Memory\Memory;
use Startwind\Inventorio\Reporter\InventorioGradeReporter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GradeCommand extends CollectorCommand
{
    protected static $defaultName = 'grade';
    protected static $defaultDescription = 'Grade this server';

    private const NOT_APPLICABLE = 'not applicable';
    
    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initConfiguration($input->getOption('configFile'));

        $debugMode = $input->getOption('debug');

        if (!$this->isInitialized()) {
            $output->writeln('<error>System was not initialized. Please run inventorio init.</error>');
            return Command::FAILURE;
        }

        $this->initCollectors();

        $inventory = [];

        $client = new \Startwind\Inventorio\Util\Client(new Client());

        foreach ($this->collectors as $collector) {
            if ($collector instanceof InventoryAwareCollector) {
                $collector->setInventory($inventory);
            }
            if ($collector instanceof ClientAwareCollector) {
                $collector->setClient($client);
            }

            if ($debugMode) $start = time();
            $collected = $collector->collect();
            if ($collected) {
                $inventory[$collector->getIdentifier()] = $collected;
            } else {
                $inventory[$collector->getIdentifier()] = self::NOT_APPLICABLE;
            }
            if ($debugMode) {
                $output->writeln('DEBUG: running ' . $collector->getIdentifier() . ' took ' . time() - $start . ' seconds');
            }
        }

        if ($debugMode) {
            $output->writeln('DEBUG: collection result:');
            $output->writeln(json_encode($inventory, JSON_PRETTY_PRINT));
        }

        Memory::getInstance()->setCollection($inventory);

        $reporter = new InventorioGradeReporter($output, $this->config->getInventorioServer(), $this->getServerId(), $this->getUserId());

        try {
            $reporter->report($inventory);
        } catch (Exception $exception) {
            $output->writeln('<error>                           ');
            $output->writeln('  Unable to run reporter.  ');
            $output->writeln('                           </error>');
            $output->writeln('');
            $output->writeln(' <comment>Message: ' . $exception->getMessage() . '</comment>');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
