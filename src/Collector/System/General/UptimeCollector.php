<?php

namespace Startwind\Inventorio\Collector\System\General;

use DateTime;
use Startwind\Inventorio\Collector\Collector;
use Startwind\Inventorio\Exec\File;
use Startwind\Inventorio\Exec\Runner;
use Startwind\Inventorio\Exec\System;

/**
 * This collector returns details about the operating system.
 *
 * - family: the OS family (MacOs, Linux, Windows). Please use the provided constants.
 * - version: the OS version
 *
 */
class UptimeCollector implements Collector
{
    protected const COLLECTION_IDENTIFIER = 'Uptime';

    /**
     * @inheritDoc
     */
    public function getIdentifier(): string
    {
        return self::COLLECTION_IDENTIFIER;
    }

    /**
     * @inheritDoc
     */
    public function collect(): array
    {
        $os = strtolower(System::getInstance()->getPlatform());

        $date = false;

        $runner = Runner::getInstance();
        $file = File::getInstance();

        if ($os === 'linux') {
            if ($runner->fileExists("/proc/uptime")) {
                $uptime = $file->getContents("/proc/uptime");
                $uptime = explode(" ", $uptime);
                $seconds = floor((int)$uptime[0]);
                $bootTimestamp = time() - $seconds;
                $date = date(DateTime::ATOM, (int)$bootTimestamp);
            }
        } elseif ($os === 'darwin') { // macOS
            $output = $runner->run("sysctl -n kern.boottime")->getOutput();
            if (preg_match('/sec = (\d+)/', $output, $matches)) {
                $bootTimestamp = (int)$matches[1];
                $date = date(DateTime::ATOM, $bootTimestamp);
            }
        } elseif ($os === 'windows') {
            $output = $runner->run("net stats srv")->getOutput();
            if ($output && preg_match('/Statistik seit (.*)/i', $output, $matches)) {
                $bootTimeStr = trim($matches[1]);
                $bootTimestamp = strtotime($bootTimeStr);
                if ($bootTimestamp !== false) {
                    $date = date(DateTime::ATOM, $bootTimestamp);
                }
            }
        }

        if ($date) {
            return ['date' => $date];
        } else {
            return [];
        }
    }
}
