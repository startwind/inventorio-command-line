<?php

namespace Startwind\Inventorio\Command;

use Startwind\Inventorio\Collector\Application\ProgrammingLanguage\PhpCollector;
use Startwind\Inventorio\Collector\OperatingSystem\OperatingSystemCollector;
use Startwind\Inventorio\Collector\Package\Brew\BrewPackageCollector;
use Startwind\Inventorio\Reporter\CliReporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:collect')]
class CollectCommand extends Command
{
    private const string NOT_APPLICABLE = 'not applicable';

    /**
     * @var \Startwind\Inventorio\Collector\Collector[]
     */
    private array $collectors = [];

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
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

        $reporter = new CliReporter($output);

        $reporter->report($inventory);

        return Command::SUCCESS;
    }

    private function initCollectors(): void
    {
        $this->collectors[] = new OperatingSystemCollector();

        // Package Managers
        $this->collectors[] = new BrewPackageCollector();

        // Application / Programming Language
        $this->collectors[] = new PhpCollector();
    }
}
