<?php

namespace Startwind\Inventorio\Collector\System\Logs;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Startwind\Inventorio\Collector\Collector;
use Startwind\Inventorio\Exec\File;

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

    private function getLogFileStatus(): array
    {
        $fileHandler = File::getInstance();

        $searchPath = '/var/log';

        $logrotateConfs = ['/etc/logrotate.conf'];

        if ($fileHandler->isDir('/etc/logrotate.d')) {
            $entries = $fileHandler->scanDir('/etc/logrotate.d');
            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') continue;

                $fullPath = '/etc/logrotate.d/' . $entry;
                if (is_file($fullPath)) {
                    $logrotateConfs[] = $fullPath;
                }
            }
        }

        // Step 1: Find all .log files under /var/log
        $allLogs = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($searchPath, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (preg_match('/\.log$/', $file->getFilename())) {
                $realPath = $fileHandler->realPath($file->getPathname());
                if ($realPath !== false && $fileHandler->isFile($realPath)) {
                    $allLogs[$realPath] = [
                        'size' => $fileHandler->getFilesize($realPath),
                        'last_modified' => filemtime($realPath)
                    ];
                }
            }
        }

        // Step 2: Extract managed log paths from logrotate config files
        $explicitManaged = [];
        foreach ($logrotateConfs as $confFile) {
            if (!$fileHandler->isReadable($confFile)) continue;
            $lines = $fileHandler->getContents($confFile, true);
            foreach ($lines as $line) {
                if (preg_match('#^\s*/[^\s{}]+\.log#', $line, $matches)) {
                    $path = $fileHandler->realPath($matches[0]);
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
            $dir = dirname($logFile);
            $base = basename($logFile);

            $entries = File::getInstance()->scanDir($dir);
            $found = false;

            foreach ($entries as $entry) {
                if (
                    strpos($entry, $base . '.') === 0 &&
                    File::getInstance()->isFile($dir . '/' . $entry)
                ) {
                    $found = true;
                    break;
                }
            }

            if ($found) {
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

            $index = str_replace('/', '-', $path);

            if (in_array($path, $allManaged)) {
                $result['managed'][$index] = $entry;
            } else {
                $result['unmanaged'][$index] = $entry;
            }
        }

        return $result;
    }
}
