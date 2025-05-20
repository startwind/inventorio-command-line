<?php

namespace Startwind\Inventorio\Exec;

use Symfony\Component\Process\Process;
use function RectorPrefix202308\Symfony\Component\String\s;

class Runner
{
    private bool $timeout;

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
        $process = new Process(['timeout', '--version']);
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
}