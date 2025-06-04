<?php

namespace Startwind\Inventorio\Command;

use Startwind\Inventorio\Collector\Application\Monitoring\WebProsMonitoringCollector;
use Startwind\Inventorio\Collector\Application\ProgrammingLanguage\PhpCollector;
use Startwind\Inventorio\Collector\Application\WebServer\Apache\ApacheConfigurationCollector;
use Startwind\Inventorio\Collector\Application\WebServer\Apache\ApacheServerNameCollector;
use Startwind\Inventorio\Collector\Collector;
use Startwind\Inventorio\Collector\Hosting\HostingCompany\ASNCollector;
use Startwind\Inventorio\Collector\Inventorio\CommandCollector;
use Startwind\Inventorio\Collector\Inventorio\InventorioCollector;
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
use Startwind\Inventorio\Collector\Website\ResponseCollector;
use Startwind\Inventorio\Collector\Website\WordPress\DatabaseCredentialCollector;
use Startwind\Inventorio\Collector\Website\WordPress\WordPressCollector;

abstract class CollectorCommand extends InventorioCommand
{
    /**
     * @var Collector[]
     */
    protected array $collectors = [];


    /**
     * Initialize all collectors.
     *
     * @todo use a config file to add collectors
     */
    protected function initCollectors(): void
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
        $this->collectors[] = new DatabaseCredentialCollector();
        $this->collectors[] = new ResponseCollector();
    }
}
