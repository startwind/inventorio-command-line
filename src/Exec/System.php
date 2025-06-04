<?php

namespace Startwind\Inventorio\Exec;

class System
{
    private static ?System $instance = null;

    public static function getInstance(): System
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getDiskTotalSpace($directory): float
    {
        $escaped = escapeshellarg($directory);
        $cmd = "df -k $escaped | awk 'NR==2 {print \$2 * 1024}'";
        $cmd = 'df -k ' . $escaped . '| awk "NR==2 {print \$2 * 1024}"';
        $output = trim(Runner::getInstance()->run($cmd)->getOutput());
        return is_numeric($output) ? (float)$output : 0.0;
    }

    public function getDiskFreeSpace($directory): float
    {
        $escaped = escapeshellarg($directory);
        $cmd = 'df -k ' . $escaped . '| awk "NR==2 {print \$4 * 1024}"';
        $output = trim(Runner::getInstance()->run($cmd)->getOutput());
        return is_numeric($output) ? (float)$output : 0.0;
    }

    public function getLoadAverage(): array
    {
        $output = trim(Runner::getInstance()->run('uptime')->getOutput());

        if (preg_match('/load averages?: ([\d\.,]+)[, ]+([\d\.,]+)[, ]+([\d\.,]+)/', $output, $matches)) {
            return [
                (float)str_replace(',', '.', $matches[1]),
                (float)str_replace(',', '.', $matches[2]),
                (float)str_replace(',', '.', $matches[3]),
            ];
        }

        return [];
    }

    public function getEnvironmentVariable($name): string
    {
        $escaped = escapeshellarg($name);
        $cmd = "printenv $escaped";
        return trim(Runner::getInstance()->run($cmd)->getOutput());
    }

    public function getPlatform(): string
    {
        static $platform;

        if (!$platform) {
            $platform = trim(Runner::getInstance()->run('uname -s')->getOutput());
        }

        return $platform;
    }
}