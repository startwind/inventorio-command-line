<?php

namespace Startwind\Inventorio\Exec;

class System
{
    private static ?System $instance = null;

    static public function getInstance(): System
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getDiskTotalSpace($directory): float
    {
        return disk_total_space($directory);
    }

    public function getDiskFreeSpace($directory): float
    {
        return disk_free_space($directory);
    }

    public function getLoadAverage(): array
    {
        return sys_getloadavg();
    }

    public function getEnvironmentVariable($name): string
    {
        return getenv($name);
    }
}
