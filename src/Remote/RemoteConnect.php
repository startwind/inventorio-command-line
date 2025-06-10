<?php

namespace Startwind\Inventorio\Remote;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Process\Process;

class RemoteConnect
{
    private const URL_HAS_COMMAND = '/inventorio/command/queued/{serverId}';
    private const URL_POP_COMMAND = '/inventorio/command/pop/{serverId}';
    private const URL_SEND_OUTPUT = '/inventorio/command/result/{commandId}';

    private string $inventorioServer;
    private string $serverId;
    private array $commands;
    private string $secret;

    public function __construct(string $inventorioServer, string $serverId, array $commands, string $secret)
    {
        $this->inventorioServer = $inventorioServer;
        $this->serverId = $serverId;
        $this->commands = $commands;
        $this->secret = $secret;
    }

    public function run($remoteEnabled, $smartCareEnabled): string
    {
        $client = new Client();

        $popUrl = str_replace('{serverId}', $this->serverId, self::URL_POP_COMMAND);
        $hasUrl = str_replace('{serverId}', $this->serverId, self::URL_HAS_COMMAND);

        $response = $client->get($this->inventorioServer . $hasUrl);
        $result = json_decode($response->getBody(), true);

        if ($result['data']['hasQueued']) {
            $commandResponse = $client->get($this->inventorioServer . $popUrl);
            $commandResult = json_decode($commandResponse->getBody(), true);

            $identifier = $commandResult['data']['command']['id'];

            if ($commandResult['data']['command']['type'] === 'smartCare' || !$smartCareEnabled) {
                $commandOutput = [
                    "output" => '',
                    'error' => 'SmartCare is not activated on this server',
                    'actualCommand' => '<unknown>',
                    'exitCode' => Command::FAILURE
                ];
            } elseif ($commandResult['data']['command']['type'] === 'remote' || !$remoteEnabled) {
                $commandOutput = [
                    "output" => '',
                    'error' => 'Remote commands are not enabled on this server',
                    'actualCommand' => '<unknown>',
                    'exitCode' => Command::FAILURE
                ];
            } else {
                if ($commandResult['type'] === 'remote') {

                    $commandId = $commandResult['data']['command']['command'];

                    $expectedProof = md5($commandId . $this->secret);
                    $cloudProof = $commandResult['data']['command']['proof'];

                    if ($expectedProof !== $cloudProof) {
                        $cloudCommand = $commandResult['data']['command']['storedCommand']['command'];
                        $commandOutput = $this->runCommand($commandId, $cloudCommand);
                    } else {
                        $commandOutput = [
                            "output" => '',
                            'error' => 'The authenticity of the job could not be verified.',
                            'actualCommand' => '<unknown>',
                            'exitCode' => Command::FAILURE
                        ];
                    }
                } else {
                    var_dump($commandResult);
                    $identifier = $commandResult['data']['command']['id'];
                }
            }

            $sendUrl = str_replace('{commandId}', $identifier, self::URL_SEND_OUTPUT);

            $client->post($this->inventorioServer . $sendUrl, [
                RequestOptions::JSON => ['output' => $commandOutput]
            ]);

            return 'Command: ' . $commandOutput['actualCommand'];
        }

        return "";
    }

    private function runCommand(string $command, string $cloudCommand): array
    {
        if (!array_key_exists($command, $this->commands)) {
            return [
                "output" => '',
                "error" => "No command with identifier '" . $command . "' found.",
                'actualCommand' => '<unknown>',
                'exitCode' => Command::FAILURE
            ];
        }

        $actualCommand = $this->commands[$command];

        if ($cloudCommand === $actualCommand['command']) {
            $shellCommandLine = "timeout --kill-after=5s 1m " . $actualCommand['command'];
            $process = Process::fromShellCommandline($shellCommandLine);
            $process->run();

            return [
                'output' => $process->getOutput(),
                'error' => $process->getErrorOutput(),
                'actualCommand' => $actualCommand['command'],
                'exitCode' => $process->getExitCode()
            ];
        } else {
            return [
                'output' => '',
                'error' => 'The command that should be run is not the same as the one that was triggered. Looks like somebody tried to hack your system.',
                'actualCommand' => $actualCommand['command'],
                'exitCode' => Command::FAILURE
            ];
        }
    }

}
