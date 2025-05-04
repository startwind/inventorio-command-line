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
            'logfiles' => $this->getLogFileStatus()
        ];
    }

    private function getLogFileStatus(): array {
        $searchPath = '/var/log';
        $logrotateConfs = ['/etc/logrotate.conf', ...glob('/etc/logrotate.d/*')];

        // Step 1: Find all .log files under /var/log
        $allLogs = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($searchPath, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (preg_match('/\.log$/', $file->getFilename())) {
                $realPath = realpath($file->getPathname());
                if ($realPath !== false && is_file($realPath)) {
                    $allLogs[$realPath] = [
                        'size' => filesize($realPath),
                        'last_modified' => filemtime($realPath)
                    ];
                }
            }
        }

        // Step 2: Extract managed log paths from logrotate config files
        $explicitManaged = [];
        foreach ($logrotateConfs as $confFile) {
            if (!is_readable($confFile)) continue;
            $lines = file($confFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (preg_match('#^\s*/[^\s{}]+\.log#', $line, $matches)) {
                    $path = realpath(trim($matches[0]));
                    if ($path !== false) {
                        $explicitManaged[] = $path;
                    }
                }
            }
        }

        $explicitManaged = array_unique($explicitManaged);

        // Step 3: Check for rotated versions (*.log.1, *.log.2.gz, etc.)
        $rotatedManaged = [];
        foreach (array_keys($allLogs) as $logFile) {
            $rotatedGlob = glob($logFile . '.*'); // e.g., /var/log/example.log.*
            if ($rotatedGlob) {
                $rotatedManaged[] = $logFile;
            }
        }

        // Combine explicit config-based and detected rotated logs
        $allManaged = array_unique(array_merge($explicitManaged, $rotatedManaged));

        // Step 4: Build result
        $result = [
            'managed' => [],
            'unmanaged' => []
        ];

        foreach ($allLogs as $path => $info) {
            $entry = [
                'path' => $path,
                'size' => $info['size'],
                'last_modified' => date('c', $info['last_modified']) // ISO 8601 format
            ];

            if (in_array($path, $allManaged)) {
                $result['managed'][] = $entry;
            } else {
                $result['unmanaged'][] = $entry;
            }
        }

        return $result;
    }
}
