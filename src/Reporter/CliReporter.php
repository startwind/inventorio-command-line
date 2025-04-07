<?php

namespace Startwind\Inventorio\Reporter;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Report the data to the command line. Used for testing purposes.
 */
class CliReporter implements Reporter
{
    private OutputInterface $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @inheritDoc
     */
    public function report(array $collectionData): void
    {
        $this->output->writeln(json_encode($collectionData, JSON_PRETTY_PRINT));
    }
}
