<?php

namespace Startwind\Inventorio\Collector\System\General;

use Startwind\Inventorio\Collector\BasicCollector;

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
        $bytesFree = disk_free_space($path);
        $bytesTotal = disk_total_space($path);

        if ($bytesFree === false || $bytesTotal === false || $bytesTotal == 0) {
            return [];
        }

        $gbFree = round($bytesFree / 1024 / 1024 / 1024, 2);
        $percentFree = ($bytesFree / $bytesTotal) * 100;
        $percentFreeRounded = floor($percentFree); // immer abrunden

        if ($percentFreeRounded < 20) {
            $score = 0;
        } elseif ($percentFreeRounded < 40) {
            $score = 25;
        } elseif ($percentFreeRounded < 60) {
            $score = 50;
        } elseif ($percentFreeRounded < 80) {
            $score = 75;
        } else {
            $score = 100;
        }

        return [
            'gb_free' => $gbFree,
            'percent_free' => $percentFreeRounded,
            'score' => $score
        ];
    }
}
