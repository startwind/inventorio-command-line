<?php

namespace Startwind\Inventorio\Exec;

use Symfony\Component\Process\Process;

abstract class Runner
{
    public static function run($command, $killAfterSeconds = 5): Process
    {
        $shellCommandLine = "timeout --kill-after=" . $killAfterSeconds . "s 1m " . $command;

        $process = Process::fromShellCommandline($shellCommandLine);

        $process->run();

        return $process;
    }
}