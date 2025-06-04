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
        return is_dir($directory);
    }

    public function isFile($filename): bool
    {
        return is_file($filename);
    }

    public function isLink($filename): bool
    {
        return is_link($filename);
    }

    public function isReadable($filename): bool
    {
        return is_readable($filename);
    }

    public function getFilesize($filename): int
    {
        return filesize($filename);
    }

    public function fileExists(string $path): bool
    {
        return file_exists($path);
    }

    public function getContents(string $path, bool $asArray = false): string|false|array
    {
        if ($asArray) {
            return file($path);
        } else {
            return file_get_contents($path);
        }
    }

    public function realPath(string $path): string
    {
        return Runner::getInstance()->run("realpath " . escapeshellarg(trim($path)))->getOutput();
    }

    public function scanDir($path): array
    {
        return scandir($path);
    }
}
