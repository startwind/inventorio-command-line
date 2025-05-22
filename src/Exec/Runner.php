<?php

namespace Startwind\Inventorio\Exec;

use Symfony\Component\Process\Process;

class Runner
{
    private bool $timeout = false;

    private static ?Runner $instance = null;

    static public function getInstance(): Runner
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct()
    {
        $process = $this->run('timeout --version');
        if ($process->isSuccessful()) {
            $this->timeout = true;
        } else {
            $this->timeout = false;
        }
    }

    public function run($command, $killAfterSeconds = 5): Process
    {
        if ($this->timeout) {
            $shellCommandLine = "timeout --kill-after=" . $killAfterSeconds . "s 1m " . $command;
        } else {
            $shellCommandLine = $command;
        }

        $process = Process::fromShellCommandline($shellCommandLine);

        $process->run();

        return $process;
    }

    public function commandExists(string $command): bool
    {
        $which = $this->run('which ' . $command)->getOutput();
        if (empty($which)) {
            return false;
        }

        return true;
    }

    public static function outputToArray(string $output): array
    {
        return explode("\n", trim($output));
    }

    public function getFileContents(string $path, bool $asArray = false): string|false|array
    {
        if ($asArray) {
            return file($path);
        } else {
            return file_get_contents($path);
        }
    }

    public function fileExists(string $path): bool
    {
        return file_exists($path);
    }
}