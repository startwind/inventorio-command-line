<?php

namespace Startwind\Inventorio\Collector\System\Logs;

use Startwind\Inventorio\Collector\Collector;

class LogrotateCollector implements Collector
{
    public function getIdentifier(): string
    {
        return 'SystemLogLogrotate';
    }

    public function collect(): array
    {
        return [
            'logfiles' => $this->findLogFiles()
        ];
    }

    function findLogFiles(): array
    {
        // Only search under /var/log
        $searchPath = '/var/log';

        // Paths to logrotate configuration files
        $logrotateConfs = ['/etc/logrotate.conf', ...glob('/etc/logrotate.d/*')];

        // Step 1: Find all .log files under /var/log
        $allLogs = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($searchPath, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (preg_match('/\.log$/', $file->getFilename())) {
                $allLogs[] = realpath($file->getPathname());
            }
        }

        // Step 2: Extract managed log file paths from logrotate configs
        $managedLogs = [];
        foreach ($logrotateConfs as $confFile) {
            if (!is_readable($confFile)) continue;
            $lines = file($confFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                // Look for lines that contain a log file path
                if (preg_match('#^\s*/[^\s{}]+\.log#', $line, $matches)) {
                    $managedLogs[] = realpath(trim($matches[0]));
                }
            }
        }

        // Step 3: Filter out invalid paths and duplicates
        $allLogs = array_unique(array_filter($allLogs));
        $managedLogs = array_unique(array_filter($managedLogs));

        // Step 4: Return logs that are not listed in logrotate configs
        return [
            'unmanaged' => array_values(array_diff($allLogs, $managedLogs)),
            'managed' => $managedLogs
        ];
    }
}
