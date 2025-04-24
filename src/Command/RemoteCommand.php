<?php

namespace Startwind\Inventorio\Command;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class RemoteCommand extends InventorioCommand
{
    protected static $defaultName = 'remote';
    protected static $defaultDescription = 'Start remote mode';

    private const URL_HAS_COMMAND = '/inventorio/command/queued/{serverId}';
    private const URL_POP_COMMAND = '/inventorio/command/pop/{serverId}';
    private const URL_SEND_OUTPUT = '/inventorio/command/result/{commandId}';

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initConfiguration($input->getOption('configFile'));

        $client = new Client();

        $popUrl = str_replace('{serverId}', $this->getServerId(), self::URL_POP_COMMAND);
        $hasUrl = str_replace('{serverId}', $this->getServerId(), self::URL_HAS_COMMAND);

        while (true) {
            $response = $client->get($this->config->getInventorioServer() . $hasUrl);
            $result = json_decode($response->getBody(), true);

            if ($result['data']['hasQueued']) {
                $commandResponse = $client->get($this->config->getInventorioServer() . $popUrl);
                $commandResult = json_decode($commandResponse->getBody(), true);

                $command = $commandResult['data']['command']['command'];
                $identifier = $commandResult['data']['command']['id'];

                $output->writeln('Running: ' . $command);

                $commandOutput = $this->runCommand($command);

                $sendUrl = str_replace('{commandId}', $identifier, self::URL_SEND_OUTPUT);

                $client->post($this->config->getInventorioServer() . $sendUrl, [
                    RequestOptions::JSON => ['output' => $commandOutput]
                ]);
            }
            sleep(10);
        }
    }

    private function runCommand($command): array
    {
        $commands = $this->config->getCommands();

        if (!array_key_exists($command, $commands)) {
            return [
                "output" => "No command with identifier '" . $command . "' found."
            ];
        }

        $actualCommand = $commands[$command];

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
