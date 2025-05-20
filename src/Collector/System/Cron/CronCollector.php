<?php

namespace Startwind\Inventorio\Collector\System\Cron;

use Startwind\Inventorio\Collector\Collector;
use Startwind\Inventorio\Exec\Runner;

/**
 * This collector returns details about the operating system.
 *
 * - family: the OS family (MacOs, Linux, Windows). Please use the provided constants.
 * - version: the OS version
 *
 */
class CronCollector implements Collector
{
    protected const COLLECTION_IDENTIFIER = 'CronJobs';

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
        $cronJobs = Runner::getInstance()->run('crontab -l 2>&1')->getOutput();

        if (strpos($cronJobs, 'no crontab for') !== false) {
            return [];
        }

        $cronJobs = explode("\n", $cronJobs);

        $cronJobsResult = [];

        foreach ($cronJobs as $cronJob) {
            if ($cronJob == "" || str_starts_with($cronJob, '#') || $cronJob == 'SHELL=/bin/bash') {
                continue;
            }

            $parts = preg_split('/\s+/', $cronJob, 6);

            $cronJobsResult[] = [
                'minute' => $parts[0],
                'hour' => $parts[1],
                'dayOfMonth' => $parts[2],
                'month' => $parts[3],
                'dayOfWeek' => $parts[4],
                'command' => $parts[5]
            ];
        }


        return ['cronjobs' => $cronJobsResult];
    }
}
