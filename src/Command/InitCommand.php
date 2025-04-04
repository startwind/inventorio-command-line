<?php

namespace Startwind\Inventorio\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:init')]
class InitCommand extends InventorioCommand
{
    private const string SERVER_ID_PREFIX = 'inv-srv-';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->isInitialized()) {
            $output->writeln('<info>System is already initialized.</info>');
            return Command::SUCCESS;
        }

        $configFile = $this->getConfigFile();
        $serverId = $this->createServerId();

        $config = [
            'serverId' => $serverId
        ];

        @mkdir(dirname($configFile), 0777, true);
        file_put_contents($configFile, json_encode($config), JSON_PRETTY_PRINT);

        return Command::SUCCESS;
    }

    private function createServerId(): string
    {
        $data = random_bytes(16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return self::SERVER_ID_PREFIX . vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
