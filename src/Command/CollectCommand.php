<?php

namespace Startwind\Inventorio\Command;

use Startwind\Inventorio\Collector\Application\ProgrammingLanguage\PhpCollector;
use Startwind\Inventorio\Collector\OperatingSystem\OperatingSystemCollector;
use Startwind\Inventorio\Reporter\InventorioReporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:collect')]
class CollectCommand extends InventorioCommand
{
    private const string NOT_APPLICABLE = 'not applicable';

    /**
     * @var \Startwind\Inventorio\Collector\Collector[]
     */
    private array $collectors = [];

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->isInitialized()) {
            $output->writeln('<error>System was not initialized. Please run app:init.</error>');
            return Command::FAILURE;
        }

        $this->initCollectors();

        $inventory = [];

        foreach ($this->collectors as $collector) {
            $collected = $collector->collect();
            if ($collected) {
                $inventory[$collector->getIdentifier()] = $collected;
            } else {
                $inventory[$collector->getIdentifier()] = self::NOT_APPLICABLE;
            }
        }

        $reporter = new InventorioReporter($output, $this->getServerId(), $this->getUserId());

        try {
            $reporter->report($inventory);
        } catch (\Exception $exception) {
            $output->writeln('<error>                           ');
            $output->writeln('  Unable to run reporter.  ');
            $output->writeln('                           </error>');
            $output->writeln('');
            $output->writeln(' <comment>Message: ' . $exception->getMessage() . '</comment>');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Initialize all collectors.
     *
     * @todo use a config file to
     */
    private function initCollectors(): void
    {
        $this->collectors[] = new OperatingSystemCollector();

        // Package Managers
        // $this->collectors[] = new BrewPackageCollector();

        // Application / Programming Language
        $this->collectors[] = new PhpCollector();
    }
}
