<?php

namespace Startwind\Inventorio\Collector\System\General;

use Startwind\Inventorio\Collector\Collector;

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
        $os = PHP_OS_FAMILY;

        $date = false;

        if ($os === 'Linux') {
            if (file_exists("/proc/uptime")) {
                $uptime = file_get_contents("/proc/uptime");
                $uptime = explode(" ", $uptime);
                $seconds = floor((int)$uptime[0]);
                $bootTimestamp = time() - $seconds;
                $date = date(\DateTime::ATOM, (int)$bootTimestamp);
            }
        } elseif ($os === 'Darwin') { // macOS
            $output = shell_exec("sysctl -n kern.boottime");
            if (preg_match('/sec = (\d+)/', $output, $matches)) {
                $bootTimestamp = (int)$matches[1];
                $date = date(\DateTime::ATOM, $bootTimestamp);
            }
        } elseif ($os === 'Windows') {
            $output = shell_exec("net stats srv");
            if ($output && preg_match('/Statistik seit (.*)/i', $output, $matches)) {
                $bootTimeStr = trim($matches[1]);
                $bootTimestamp = strtotime($bootTimeStr);
                if ($bootTimestamp !== false) {
                    $date = date(\DateTime::ATOM, $bootTimestamp);
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
