<?php

namespace Startwind\Inventorio\Collector\Package\Dpkg;

use Startwind\Inventorio\Collector\Collector;
use Startwind\Inventorio\Collector\OperatingSystem\OperatingSystemCollector;

/**
 * This collector returns details about all installed HomeBrew packages.
 */
class DpkgPackageCollector implements Collector
{
    protected const string COLLECTION_IDENTIFIER = 'DpkgPackages';

    /**
     * @inheritDoc
     */
    public function getIdentifier(): string
    {
        return self::COLLECTION_IDENTIFIER;
    }

    /**
     * @inheritDoc
     */
    public function collect(): array
    {
        if (OperatingSystemCollector::getOsFamily() !== OperatingSystemCollector::OS_FAMILY_LINUX) {
            return [];
        }

        $installed = shell_exec('command -v dpkg-query');

        if (!$installed) {
            return [];
        }

        $packages = shell_exec('dpkg-query -l');

        $packageLines = explode("\n", trim($packages));

        $installedPackages = [];

        foreach ($packageLines as $line) {
            if (str_starts_with($line, 'ii')) {
                $packageDetails = preg_split('/\s+/', $line);
                $packageName = $packageDetails[1];
                $packageVersion = $packageDetails[2];
                $installedPackages[$packageName] = [$packageVersion];
            }
        }

        return [
            'packages' => $installedPackages
        ];
    }
}
