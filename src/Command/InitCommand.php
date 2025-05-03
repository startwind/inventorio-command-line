<?php

namespace Startwind\Inventorio\Command;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InitCommand extends InventorioCommand
{
    protected static $defaultName = 'init';
    protected static $defaultDescription = 'Initialize Inventorio';

    private const ENDPOINT_INIT = '/inventory/server/{serverId}';

    private const SERVER_ID_PREFIX = 'inv-srv-';

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        parent::configure();

        $this->addArgument('userId', InputArgument::REQUIRED, 'The inventorio user id.');
        $this->addOption('serverName', 's', InputOption::VALUE_OPTIONAL, 'The server name');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initConfiguration($input->getOption('configFile'));

        if ($this->isInitialized()) {
            $output->writeln('<info>System is already initialized.</info>');
            return Command::SUCCESS;
        }

        $serverName = $this->getServerName($input, $output);

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
            $response = $client->post($this->getPreparedEndpoint($serverId), [
                RequestOptions::JSON => $payload
            ]);
        } catch (ClientException $exception) {
            $result = json_decode((string)$exception->getResponse()->getBody(), true);
            $output->writeln('<error>Unable to initialize: ' . $result['message'] . '</error>');
            return Command::FAILURE;
        }

        $result = json_decode((string)$response->getBody(), true);

        $config = [
            'serverId' => $serverId,
            'userId' => $userId,
            'remote' => false,
            'commands' => $this->config->getCommands(false),
            'secret' => $result['data']['secret']
        ];

        if (!file_exists(dirname($configFile))) {
            mkdir(dirname($configFile), 0777, true);
        }

        file_put_contents($configFile, json_encode($config), JSON_PRETTY_PRINT);

        $output->writeln('<info>Server registered.</info>');

        return Command::SUCCESS;
    }

    /**
     * Ask the user for the server name if not set a parameter.
     */
    private function getServerName(InputInterface $input, OutputInterface $output): string
    {
        if (!$input->getOption('serverName')) {
            $io = new SymfonyStyle($input, $output);

            $defaultName = gethostname();

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

        return $serverName;
    }

    /**
     * Return the final endpoint where the collected data should be sent to.
     */
    private function getPreparedEndpoint($serverId): string
    {
        return str_replace('{serverId}', $serverId, $this->config->getInventorioServer() . self::ENDPOINT_INIT);
    }

    /**
     * Create an unique ID for the current server
     */
    private function createServerId(): string
    {
        $data = random_bytes(16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return self::SERVER_ID_PREFIX . vsprintf('%s%s-%s-%s-%s-%s%s%s', /** @scrutinizer ignore-type */ str_split(bin2hex($data), 4));
    }
}
