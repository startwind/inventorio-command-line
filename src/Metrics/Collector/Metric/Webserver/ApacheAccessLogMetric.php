<?php

namespace Startwind\Inventorio\Metrics\Collector\Metric\Webserver;

use Startwind\Inventorio\Metrics\Collector\Metric\FileLinesMetric;

class ApacheAccessLogMetric extends FileLinesMetric
{
    protected string $name = 'apache_access_log_new_lines';
    protected string $filename = '/var/log/apache2/access.log';
}