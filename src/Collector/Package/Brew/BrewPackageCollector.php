<?php

namespace Startwind\Inventorio\Collector\Package\Brew;

use Startwind\Inventorio\Collector\Collector;

/**
 * This collector returns details about all installed HomeBrew packages.
 */
class BrewPackageCollector implements Collector
{
    protected const COLLECTION_IDENTIFIER = 'HomeBrewPackages';

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
        $installed = shell_exec('command -v brew');

        if (!$installed) {
            return [];
        }

        $output = shell_exec('brew info --installed --json=v2');

        $rawData = json_decode($output, true);

        $packages = [];

        foreach ($rawData['formulae'] as $package) {
            $versions = [];

            foreach ($package['installed'] as $item) {
                $versions[] = $item['version'];
            }

            $packages[$package['name']] = $versions;
        }

        return [
            'packages' => $packages
        ];
    }
}
