<?php

namespace Startwind\Inventorio\Command;

use Startwind\Inventorio\Collector\Application\ProgrammingLanguage\PhpCollector;
use Startwind\Inventorio\Collector\Application\WebServer\Apache\ApacheServerNameCollector;
use Startwind\Inventorio\Collector\Hosting\HostingCompany\ASNCollector;
use Startwind\Inventorio\Collector\Inventorio\CommandCollector;
use Startwind\Inventorio\Collector\Inventorio\InventorioCollector;
use Startwind\Inventorio\Collector\OperatingSystem\OperatingSystemCollector;
use Startwind\Inventorio\Collector\Package\Brew\BrewPackageCollector;
use Startwind\Inventorio\Collector\Package\Dpkg\DpkgPackageCollector;
use Startwind\Inventorio\Collector\System\Cron\CronCollector;
use Startwind\Inventorio\Collector\System\General\ConfigurationCollector;
use Startwind\Inventorio\Collector\System\General\IpCollector;
use Startwind\Inventorio\Collector\System\General\UptimeCollector;
use Startwind\Inventorio\Collector\System\Ports\OpenPortsCollector;
use Startwind\Inventorio\Reporter\InventorioReporter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CollectCommand extends InventorioCommand
{
    protected static $defaultName = 'collect';
    protected static $defaultDescription = 'Collect metrics for Inventorio';

    private const NOT_APPLICABLE = 'not applicable';

    /**
     * @var \Startwind\Inventorio\Collector\Collector[]
     */
    private array $collectors = [];

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initConfiguration($input->getOption('configFile'));

        if (!$this->isInitialized()) {
            $output->writeln('<error>System was not initialized. Please run inventorio init.</error>');
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

        $reporter = new InventorioReporter($output, $this->config->getInventorioServer(), $this->getServerId(), $this->getUserId());

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
        // Inventorio
        $this->collectors[] = new InventorioCollector();
        $this->collectors[] = new CommandCollector($this->config);
        // $this->collectors[] = new RandomCollector();

        // General
        $this->collectors[] = new OperatingSystemCollector();

        // Hosting
        $this->collectors[] = new ASNCollector();

        // System / General
        $this->collectors[] = new IpCollector();
        $this->collectors[] = new UptimeCollector();
        $this->collectors[] = new OpenPortsCollector();
        $this->collectors[] = new ConfigurationCollector();
        $this->collectors[] = new CronCollector();

        // Package Managers
        $this->collectors[] = new BrewPackageCollector();
        $this->collectors[] = new DpkgPackageCollector();

        // Application / Programming Language
        $this->collectors[] = new PhpCollector();

        // Application / WebServer
        $this->collectors[] = new ApacheServerNameCollector();
    }
}
