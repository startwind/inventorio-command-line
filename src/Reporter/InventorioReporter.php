<?php

namespace Startwind\Inventorio\Reporter;

use Symfony\Component\Console\Output\OutputInterface;

class InventorioReporter implements Reporter
{
    private const string ENDPOINT_COLLECT = '';

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    private OutputInterface $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function report(array $collectionData, string $serverId): void
    {
        $this->output->writeln('Sending data for server with id: ' . $serverId);
        // $this->output->writeln(json_encode($collectionData, JSON_PRETTY_PRINT));
    }
}
