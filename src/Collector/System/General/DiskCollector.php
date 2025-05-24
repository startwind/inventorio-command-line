<?php

namespace Startwind\Inventorio\Collector\System\General;

use Startwind\Inventorio\Collector\BasicCollector;
use Startwind\Inventorio\Exec\System;

/**
 * This collector returns details about the operating system.
 *
 * - family: the OS family (MacOs, Linux, Windows). Please use the provided constants.
 * - version: the OS version
 *
 */
class DiskCollector extends BasicCollector
{
    protected string $identifier = 'SystemHardDisk';

    /**
     * @inheritDoc
     */
    public function collect(): array
    {
        return [
            '/' => $this->getDiskFreeInfo('/')
        ];
    }

    private function getDiskFreeInfo(string $path = '/'): array
    {
        $bytesFree = System::getInstance()->getDiskFreeSpace($path);
        $bytesTotal = System::getInstance()->getDiskTotalSpace($path);

        if ($bytesFree === false || $bytesTotal === false || $bytesTotal == 0) {
            return [];
        }

        $gbFree = round($bytesFree / 1024 / 1024 / 1024, 2);
        $percentFree = ($bytesFree / $bytesTotal) * 100;
        $percentFreeRounded = floor($percentFree); // immer abrunden

        if ($percentFreeRounded < 20) {
            $score = 100;
        } elseif ($percentFreeRounded < 40) {
            $score = 75;
        } elseif ($percentFreeRounded < 60) {
            $score = 50;
        } elseif ($percentFreeRounded < 80) {
            $score = 25;
        } else {
            $score = 0;
        }

        return [
            'gb_free' => $gbFree,
            'percent_free' => $percentFreeRounded,
            'score' => $score
        ];
    }
}
