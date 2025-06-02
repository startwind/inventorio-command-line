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
        $lineCount = $this->getLineCount($this->filename);

        // this is the first run and last value is therefore -1
        if ($lastValue < 0) {
            Memory::getInstance()->addData($this->getName(), $lineCount);
            return 0;
        }

        // we assume that there was a log rotation done here
        if ($lineCount < $lastValue) {
            return $lineCount;
        }

        return $lineCount - $lastValue;
    }

    protected function getLineCount($path): int
    {
        $count = Runner::getInstance()->run('wc -l ' . $path)->getOutput();
        return (int)$count;
    }
}