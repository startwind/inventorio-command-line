<?php

namespace Startwind\Inventorio\Exec;

class File
{
    private static ?File $instance = null;

    static public function getInstance(): File
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function isDir($directory): bool
    {
        $cmd = '[ -d ' . escapeshellarg($directory) . ' ] && echo "1" || echo "0"';
        return trim(Runner::getInstance()->run($cmd)->getOutput()) === '1';
    }

    public function isFile($filename): bool
    {
        $cmd = '[ -f ' . escapeshellarg($filename) . ' ] && echo "1" || echo "0"';
        return trim(Runner::getInstance()->run($cmd)->getOutput()) === '1';
    }

    public function isLink($filename): bool
    {
        $cmd = '[ -L ' . escapeshellarg($filename) . ' ] && echo "1" || echo "0"';
        return trim(Runner::getInstance()->run($cmd)->getOutput()) === '1';
    }

    public function isReadable($filename): bool
    {
        $cmd = '[ -r ' . escapeshellarg($filename) . ' ] && echo "1" || echo "0"';
        return trim(Runner::getInstance()->run($cmd)->getOutput()) === '1';
    }

    public function getFilesize($filename): int
    {
        $escaped = escapeshellarg($filename);
        $platform = System::getInstance()->getPlatform();

        if ($platform === 'darwin' || str_contains($platform, 'bsd')) {
            $cmd = 'stat -f%z ' . $escaped;
        } else {
            $cmd = 'stat -c%s ' . $escaped;
        }

        $output =trim(Runner::getInstance()->run($cmd)->getOutput());
        return is_numeric($output) ? (int)$output : 0;
    }

    public function fileExists(string $path): bool
    {
        $cmd = '[ -e ' . escapeshellarg($path) . ' ] && echo "1" || echo "0"';
        return trim(Runner::getInstance()->run($cmd)->getOutput()) === '1';
    }

    public function getContents(string $path, bool $asArray = false): string|false|array
    {
        $cmd = 'cat ' . escapeshellarg($path) . ' 2>/dev/null';
        $output = Runner::getInstance()->run($cmd)->getOutput();

        if ($output === null) {
            return false;
        }

        return $asArray ? explode("\n", rtrim($output)) : $output;
    }

    public function realPath(string $path): string
    {
        return trim(Runner::getInstance()->run("realpath " . escapeshellarg(trim($path)))->getOutput());
    }

    public function scanDir($path): array
    {
        $cmd = 'ls -1A ' . escapeshellarg($path) . ' 2>/dev/null';
        $output = Runner::getInstance()->run($cmd)->getOutput();
        return $output !== null ? explode("\n", trim($output)) : [];
    }
}
