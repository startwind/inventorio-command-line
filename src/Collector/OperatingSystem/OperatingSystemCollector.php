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
    public const string OS_FAMILY_MAC = 'macos';
    public const string OS_FAMILY_LINUX = 'linux';
    public const string OS_FAMILY_WINDOWS = 'windows';
    public const string OS_FAMILY_UNKNOWN = 'unknown';
    public const string OS_VERSION_UNKNOWN = 'unknown';

    protected const string COLLECTION_IDENTIFIER = 'OperatingSystem';

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
        $osFamily = $this->getOsFamily();

        $data = [
            'family' => $osFamily,
            'version' => $this->getOsVersion($osFamily)
        ];

        if ($osFamily == self::OS_FAMILY_LINUX) {
            $data['distribution'] = $this->getOsDistribution();
        }

        return $data;
    }

    /**
     * Return the version of the operating system
     */
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

    /**
     * Return the distribution of the linux operating system
     */
    private function getOsDistribution(): string
    {
        $osRelease = file_get_contents('/etc/os-release');
        $lines = explode("\n", $osRelease);
        $osInfo = array();
        foreach ($lines as $line) {
            if (str_contains($line, "=")) {
                list($key, $value) = explode("=", $line, 2);
                $value = trim($value, '"');
                $osInfo[$key] = $value;
            }
        }

        print_r($osInfo);
        die;

        return '';
    }

    /**
     * Return the operating system family (supported: MacOs, Windows, Linux)
     */
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
