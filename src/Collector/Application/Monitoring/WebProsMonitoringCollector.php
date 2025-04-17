<?php

namespace Startwind\Inventorio\Collector\Application\Monitoring;

use Startwind\Inventorio\Collector\Collector;

class WebProsMonitoringCollector implements Collector
{
    protected const INI_FILE = '/etc/agent360-token.ini';

    protected const COLLECTION_IDENTIFIER = 'WebProsMonitoring';

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
        if (!file_exists(self::INI_FILE)) return [];

        $config = file(self::INI_FILE);

        $configArray = [];

        foreach ($config as $line) {
            if (str_starts_with('[', $line)) continue;
            $element = explode('=', $line);
            $configArray[$element[0]] = $element[1];
        }

        return [
            'server' => $configArray['server'],
            'user' => $configArray['user']
        ];
    }
}
