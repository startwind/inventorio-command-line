<?php

namespace Startwind\Inventorio\Exec;

use Spatie\Ssh\Ssh;
use Symfony\Component\Process\Process;

class Runner
{
    private bool $timeout = false;

    private static ?Runner $instance = null;

    private bool $remoteOn = false;
    private string $remoteDsn;
    private Ssh $sshConnection;

    public function setRemote($dsn)
    {
        $this->remoteOn = true;
        $this->remoteDsn = $dsn;

        $sshCredentials = explode('@', $this->remoteDsn);
        $this->sshConnection = Ssh::create($sshCredentials[0], $sshCredentials[1]);
    }

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

        // var_dump($command);

        if ($this->remoteOn) {
            // echo "DEBUG: " . $command . "\n";
            $process = $this->sshConnection->execute($command);
        } else {
            $process = Process::fromShellCommandline($shellCommandLine);
            $process->run();
        }

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
        return File::getInstance()->getContents($path, $asArray);
    }

    public function fileExists(string $path): bool
    {
        return File::getInstance()->fileExists($path);
    }
}