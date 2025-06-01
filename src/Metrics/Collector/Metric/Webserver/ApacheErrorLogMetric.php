<?php

namespace Startwind\Inventorio\Metrics\Collector\Metric\Webserver;

use Startwind\Inventorio\Metrics\Collector\Metric\FileLinesMetric;

class ApacheErrorLogMetric extends FileLinesMetric
{
    protected string $name = 'apache_error_log_new_lines';
    protected string $filename = '/var/log/apache2/error.log';
}