<?php

namespace Startwind\Inventorio\Metrics\Collector\Metric\Webserver;

use Startwind\Inventorio\Metrics\Collector\Metric\FileLinesMetric;

class ApacheAccessLogMetric extends FileLinesMetric
{
    public const IDENTIFIER = 'apache_access_log_new_lines';

    protected string $name = self::IDENTIFIER;
    protected string $filename = '/var/log/apache2/access.log';
}