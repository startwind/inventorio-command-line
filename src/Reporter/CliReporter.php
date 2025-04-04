<?php

namespace Startwind\Inventorio\Reporter;

use Symfony\Component\Console\Output\OutputInterface;

class CliReporter implements Reporter
{
    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    private OutputInterface $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function report(array $collectionData): void
    {
        $this->output->writeln(json_encode($collectionData, JSON_PRETTY_PRINT));
    }
}
