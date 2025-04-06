<?php

namespace Startwind\Inventorio\Command;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:init')]
class InitCommand extends InventorioCommand
{
    private const string ENDPOINT_INIT = InventorioCommand::INVENTORIO_SERVER . '/inventory/server/{serverId}';

    private const string SERVER_ID_PREFIX = 'inv-srv-';

    protected function configure(): void
    {
        $this->addArgument('userId', InputArgument::REQUIRED, 'The inventorio user id.');
        $this->addOption('serverName', 's', InputOption::VALUE_OPTIONAL, 'The server name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->isInitialized()) {
            $output->writeln('<info>System is already initialized.</info>');
            return Command::SUCCESS;
        }

        if (!$input->getOption('serverName')) {
            $io = new SymfonyStyle($input, $output);

            $defaultName = gethostname(); // Hostname des Rechners

            $serverName = $io->ask(
                'Please provide the name of the server (default: ' . $defaultName . ')',
                $defaultName,
                function (?string $value) {
                    if (strlen($value ?? '') < 3) {
                        throw new \RuntimeException('The server name has to be at least three characters long.');
                    }
                    return $value;
                }
            );
        } else {
            $serverName = $input->getOption('serverName');
        }

        $configFile = $this->getConfigFile();
        $serverId = $this->createServerId();

        $userId = $input->getArgument('userId');

        $client = new Client();

        $payload = [
            'userId' => $userId,
            'serverId' => $serverId,
            'serverName' => $serverName
        ];

        try {
            $client->post($this->getPreparedEndpoint($serverId), [
                RequestOptions::JSON => $payload
            ]);
        } catch (ClientException $exception) {
            $result = json_decode((string)$exception->getResponse()->getBody(), true);
            $output->writeln('<error>Unable to initialize: ' . $result['message'] . '</error>');
            return Command::FAILURE;
        }

        $config = [
            'serverId' => $serverId,
            'userId' => $userId
        ];

        @mkdir(dirname($configFile), 0777, true);
        file_put_contents($configFile, json_encode($config), JSON_PRETTY_PRINT);

        $output->writeln('<info>System initialized. You can now run the collect command.</info>');

        return Command::SUCCESS;
    }

    /**
     * Return the final endpoint where the collected data should be sent to.
     */
    private function getPreparedEndpoint($serverId): string
    {
        return str_replace('{serverId}', $serverId, self::ENDPOINT_INIT);
    }

    private function createServerId(): string
    {
        $data = random_bytes(16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return self::SERVER_ID_PREFIX . vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
