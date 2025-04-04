<?php

namespace Startwind\Inventorio\Collector\OperatingSystem;

use Startwind\Inventorio\Collector\Collector;

/**
 * This collector returns details about the operating system.
 *
 * - family: the OS family (MacOs, Linux, Windows). Please use the provided constants.
 * - version: the OS version
 *
 */
class OperatingSystemCollector implements Collector
{
    public const string OS_FAMILY_MAC = 'MacOs';
    public const string OS_FAMILY_LINUX = 'Linux';
    public const string OS_FAMILY_WINDOWS = 'Windows';
    public const string OS_FAMILY_UNKNOWN = 'Unknown';
    public const string OS_VERSION_UNKNOWN = 'Unknown';

    protected const string COLLECTION_IDENTIFIER = 'OperatingSystem';

    public function getIdentifier(): string
    {
        return self::COLLECTION_IDENTIFIER;
    }

    public function collect(): array
    {
        $osFamily = $this->getOsFamily();

        return [
            'family' => $osFamily,
            'version' => $this->getOsVersion($osFamily)
        ];
    }

    private function getOsVersion(string $family): string
    {
        switch ($family) {
            case self::OS_FAMILY_MAC:
                return trim(shell_exec('sw_vers -productVersion'));
            case self::OS_FAMILY_LINUX:
                $linux_version = trim(shell_exec('lsb_release -d 2>/dev/null | cut -f2'));
                if (!$linux_version) {
                    $linux_version = trim(shell_exec('cat /etc/os-release 2>/dev/null | grep PRETTY_NAME | cut -d= -f2 | tr -d \'"\''));
                }
                return $linux_version;
            default:
                return self::OS_VERSION_UNKNOWN;
        }
    }

    private function getOsFamily(): string
    {
        switch (strtolower(PHP_OS_FAMILY)) {
            case 'darwin':
                return self::OS_FAMILY_MAC;
            case 'linux';
                return self::OS_FAMILY_LINUX;
            case 'windows':
                return self::OS_FAMILY_WINDOWS;
            default:
                return self::OS_FAMILY_UNKNOWN;
        }
    }
}
