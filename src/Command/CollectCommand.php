<?php

namespace Startwind\Inventorio\Command;

use Exception;
use GuzzleHttp\Client;
use Startwind\Inventorio\Collector\Application\Monitoring\WebProsMonitoringCollector;
use Startwind\Inventorio\Collector\Application\ProgrammingLanguage\PhpCollector;
use Startwind\Inventorio\Collector\Application\WebServer\Apache\ApacheConfigurationCollector;
use Startwind\Inventorio\Collector\Application\WebServer\Apache\ApacheServerNameCollector;
use Startwind\Inventorio\Collector\ClientAwareCollector;
use Startwind\Inventorio\Collector\Collector;
use Startwind\Inventorio\Collector\Hosting\HostingCompany\ASNCollector;
use Startwind\Inventorio\Collector\Inventorio\CommandCollector;
use Startwind\Inventorio\Collector\Inventorio\InventorioCollector;
use Startwind\Inventorio\Collector\InventoryAwareCollector;
use Startwind\Inventorio\Collector\Metrics\MetricThresholdCollector;
use Startwind\Inventorio\Collector\OperatingSystem\OperatingSystemCollector;
use Startwind\Inventorio\Collector\Package\Brew\BrewPackageCollector;
use Startwind\Inventorio\Collector\Package\Dpkg\DpkgPackageCollector;
use Startwind\Inventorio\Collector\System\Cron\CronCollector;
use Startwind\Inventorio\Collector\System\General\ConfigurationCollector;
use Startwind\Inventorio\Collector\System\General\DiskCollector;
use Startwind\Inventorio\Collector\System\General\IpCollector;
use Startwind\Inventorio\Collector\System\General\UptimeCollector;
use Startwind\Inventorio\Collector\System\Logs\LogrotateCollector;
use Startwind\Inventorio\Collector\System\Ports\PortsCollector;
use Startwind\Inventorio\Collector\System\Security\AuthorizedKeysCollector;
use Startwind\Inventorio\Collector\System\Security\GeneralSecurityCollector;
use Startwind\Inventorio\Collector\System\Service\SystemDCollector;
use Startwind\Inventorio\Collector\System\UserCollector;
use Startwind\Inventorio\Collector\Website\HeaderCollector;
use Startwind\Inventorio\Collector\Website\WordPress\WordPressCollector;
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
     * @var Collector[]
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

        $client = new \Startwind\Inventorio\Util\Client(new Client());

        foreach ($this->collectors as $collector) {
            if ($collector instanceof InventoryAwareCollector) {
                $collector->setInventory($inventory);
            }
            if ($collector instanceof ClientAwareCollector) {
                $collector->setClient($client);
            }
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

    /**
     * Initialize all collectors.
     *
     * @todo use a config file to
     */
    private function initCollectors(): void
    {
        // Inventorio
        $this->collectors[] = new InventorioCollector(
            $this->isRemoteEnabled(),
            $this->areLogfilesEnabled(),
            $this->isCollectEnabled(),
            $this->config
        );

        $this->collectors[] = new CommandCollector($this->config);
        // $this->collectors[] = new RandomCollector();

        // General
        $this->collectors[] = new OperatingSystemCollector();

        // Hosting
        $this->collectors[] = new ASNCollector();

        // Metrics
        $this->collectors[] = new MetricThresholdCollector();

        // System / General
        $this->collectors[] = new IpCollector();
        $this->collectors[] = new UptimeCollector();
        $this->collectors[] = new PortsCollector();
        $this->collectors[] = new ConfigurationCollector();
        $this->collectors[] = new CronCollector();
        $this->collectors[] = new UserCollector();
        $this->collectors[] = new LogrotateCollector();
        $this->collectors[] = new DiskCollector();

        // System / Services
        $this->collectors[] = new SystemDCollector();

        // System / Security
        $this->collectors[] = new GeneralSecurityCollector();
        $this->collectors[] = new AuthorizedKeysCollector();

        // Package Managers
        $this->collectors[] = new BrewPackageCollector();
        $this->collectors[] = new DpkgPackageCollector();

        // Application / Programming Language
        $this->collectors[] = new PhpCollector();
        $this->collectors[] = new WebProsMonitoringCollector();

        // Application / WebServer
        $this->collectors[] = new ApacheServerNameCollector();
        $this->collectors[] = new ApacheConfigurationCollector();
        $this->collectors[] = new HeaderCollector();

        // INVENTORY AWARE
        $this->collectors[] = new WordPressCollector();
        $this->collectors[] = new \Startwind\Inventorio\Collector\Website\UptimeCollector();
    }
}
