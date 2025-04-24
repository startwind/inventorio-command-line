<?php

namespace Startwind\Inventorio\Remote;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Symfony\Component\Process\Process;

class RemoteConnect
{
    private const URL_HAS_COMMAND = '/inventorio/command/queued/{serverId}';
    private const URL_POP_COMMAND = '/inventorio/command/pop/{serverId}';
    private const URL_SEND_OUTPUT = '/inventorio/command/result/{commandId}';

    private string $inventorioServer;
    private string $serverId;
    private array $commands;

    public function __construct(string $inventorioServer, string $serverId, array $commands)
    {
        $this->inventorioServer = $inventorioServer;
        $this->serverId = $serverId;
        $this->commands = $commands;
    }

    public function run(): string
    {
        $client = new Client();

        $popUrl = str_replace('{serverId}', $this->serverId, self::URL_POP_COMMAND);
        $hasUrl = str_replace('{serverId}', $this->serverId, self::URL_HAS_COMMAND);

        $response = $client->get($this->inventorioServer . $hasUrl);
        $result = json_decode($response->getBody(), true);

        if ($result['data']['hasQueued']) {
            $commandResponse = $client->get($this->inventorioServer . $popUrl);
            $commandResult = json_decode($commandResponse->getBody(), true);

            $command = $commandResult['data']['command']['command'];
            $identifier = $commandResult['data']['command']['id'];

            $commandOutput = $this->runCommand($command);

            $sendUrl = str_replace('{commandId}', $identifier, self::URL_SEND_OUTPUT);

            $client->post($this->inventorioServer . $sendUrl, [
                RequestOptions::JSON => ['output' => $commandOutput]
            ]);

            return 'Command: ' . $commandOutput['actualCommand'];
        }

        return "";
    }

    private function runCommand($command): array
    {
        if (!array_key_exists($command, $this->commands)) {
            return [
                "output" => "No command with identifier '" . $command . "' found."
            ];
        }

        $actualCommand = $this->commands[$command];

        $process = Process::fromShellCommandline($actualCommand['command']);

        $process->run();

        return [
            'output' => $process->getOutput(),
            'error' => $process->getErrorOutput(),
            'actualCommand' => $actualCommand['command'],
            'exitCode' => $process->getExitCode()
        ];
    }

}
