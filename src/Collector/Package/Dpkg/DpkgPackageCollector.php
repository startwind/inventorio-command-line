<?php

namespace Startwind\Inventorio\Collector\Package\Dpkg;

use Startwind\Inventorio\Collector\Collector;
use Startwind\Inventorio\Collector\OperatingSystem\OperatingSystemCollector;
use Startwind\Inventorio\Exec\Runner;

/**
 * This collector returns details about all installed HomeBrew packages.
 */
class DpkgPackageCollector implements Collector
{
    protected const COLLECTION_IDENTIFIER = 'DpkgPackages';

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

        if (!Runner::getInstance()->commandExists('apt')) {
            return [];
        }

        return [
            'packages' => $this->collectPackages(),
            'updatable' => $this->collectUpdatablePackages()
        ];
    }

    private function collectUpdatablePackages(): array
    {
        $output = Runner::getInstance()->run('apt list --upgradable 2>/dev/null')->getOutput();
        $lines = explode("\n", $output);
        array_shift($lines);

        $packages = [];

        foreach ($lines as $line) {
            if (trim($line) === '') continue;

            if (preg_match('/^([^\s\/]+)\/[^\s]+\s+([^\s]+).*upgradable from: ([^\]]+)/', $line, $matches)) {
                $packages[$matches[1]] = [
                    'currentVersion' => $matches[3],
                    'newVersion' => $matches[2]
                ];
            }
        }

        return $packages;
    }

    private function collectPackages(): array
    {
        if (!Runner::getInstance()->commandExists('dpkg-query')) {
            return [];
        }

        $packages = Runner::getInstance()->run('echo "["; dpkg-query -W -f=\'{"package":"${Package}", "version":"${Version}"},\n\' | sed \'$s/},/}/\'; echo "]"')->getOutput();

        $packageList = json_decode($packages, true);

        $result = [];

        foreach ($packageList as $package) {
            $result[$package['package']] = [$package['version']];
        }

        return $result;
    }
}
