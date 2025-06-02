<?php

namespace Startwind\Inventorio\Metrics\Collector\Metric;

use Startwind\Inventorio\Exec\File;
use Startwind\Inventorio\Exec\Runner;
use Startwind\Inventorio\Metrics\Memory\Memory;

abstract class FileLinesMetric implements Metric
{
    protected string $filename = '';
    protected string $name = '';

    public function getName(): string
    {
        return $this->name;
    }

    public function isApplicable(): bool
    {
        return File::getInstance()->fileExists($this->filename);
    }

    public function getValue(float $lastValue): float
    {
        $lastLineCount = Memory::getInstance()->getLastData($this->getName() . '_absolute', -1);
        $lineCount = $this->getLineCount($this->filename);
        Memory::getInstance()->addData($this->getName() . '_absolute', $lineCount);

        // this is the first run we return 0 to skip this value
        if ($lastLineCount < 0) {
            return 0;
        }

        // we assume that there was a log rotation done here
        if ($lineCount < $lastLineCount) {
            return $lineCount;
        }

        return $lineCount - $lastLineCount;
    }

    protected function getLineCount($path): int
    {
        $count = Runner::getInstance()->run('wc -l ' . $path)->getOutput();
        return (int)$count;
    }
}