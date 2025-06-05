<?php

namespace Startwind\Inventorio\Collector\System\Logs;

use Startwind\Inventorio\Collector\Collector;
use Startwind\Inventorio\Exec\File;
use Startwind\Inventorio\Exec\Runner;
use Startwind\Inventorio\Exec\System;

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

        $logrotateConfigurations = ['/etc/logrotate.conf'];

        if ($fileHandler->isDir('/etc/logrotate.d')) {
            $entries = $fileHandler->scanDir('/etc/logrotate.d');
            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') continue;
                $fullPath = '/etc/logrotate.d/' . $entry;
                $logrotateConfigurations[] = $fullPath;
            }
        }

        $allLogs = $this->getLogFilesWithStats($searchPath);

        // Step 2: Extract managed log paths from logrotate config files
        $explicitManaged = [];
        foreach ($logrotateConfigurations as $confFile) {
            $lines = $fileHandler->getContents($confFile, true);
            foreach ($lines as $line) {
                if (preg_match('#^\s*/[^\s{}]+\.log#', $line, $matches)) {
                    // $path = $fileHandler->realPath($matches[0]);
                    $path = $matches[0];
                    $explicitManaged[] = $path;
                }
            }
        }

        $explicitManaged = array_unique($explicitManaged);

        // Step 3: Check for rotated versions (*.log.1, *.log.2.gz, etc.)
        $rotatedManaged = [];

        foreach (array_keys($allLogs) as $logFile) {
            $dir = dirname($logFile);
            $base = basename($logFile);

            $entries = $fileHandler->scanDir($dir);
            $found = false;

            foreach ($entries as $entry) {
                if (
                    str_starts_with($entry, $base . '.') &&
                    $fileHandler->isFile($dir . '/' . $entry)
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
                'last_modified' => date('c', $info['last_modified'])
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

    function getLogFilesWithStats(string $dir): array
    {
        $result = [];

        $os = strtolower(System::getInstance()->getPlatform());

        if ($os === 'linux') {
            $cmd = "find " . escapeshellarg($dir) . " -type f -name '*.log' -exec stat --format='%n\t%s\t%Y' {} + 2>/dev/null";
        } elseif ($os === 'darwin') {
            $cmd = "find " . escapeshellarg($dir) . " -type f -name '*.log' -exec stat -f '%N\t%z\t%m' {} + 2>/dev/null";
        } else {
            return [];
        }

        $output = Runner::getInstance()->run($cmd)->getOutput();
        $output = explode("\n", $output);

        foreach ($output as $line) {
            $parts = preg_split('/\s+/', $line, 3);

            if (count($parts) !== 3) {
                continue;
            }

            [$file, $size, $mtime] = $parts;

            $result[$file] = [
                'size' => (int)$size,
                'last_modified' => (int)$mtime,
            ];
        }

        return $result;
    }
}
