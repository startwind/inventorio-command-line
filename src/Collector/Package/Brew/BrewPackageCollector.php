<?php

namespace Startwind\Inventorio\Collector\Package\Brew;

use Startwind\Inventorio\Collector\Collector;

/**
 * This collector returns details about all installed HomeBrew packages.
 */
class BrewPackageCollector implements Collector
{
    protected const string COLLECTION_IDENTIFIER = 'HomeBrewPackages';

    public function getIdentifier(): string
    {
        return self::COLLECTION_IDENTIFIER;
    }

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

            $packages[] = [
                'name' => $package['name'],
                'versions' => $versions
            ];
        }

        return [
            'packages' => $packages
        ];
    }
}
