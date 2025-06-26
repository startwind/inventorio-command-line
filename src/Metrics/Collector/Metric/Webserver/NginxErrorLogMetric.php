<?php

namespace Startwind\Inventorio\Metrics\Collector\Metric\Webserver;

use Startwind\Inventorio\Metrics\Collector\Metric\FileLinesMetric;

class NginxErrorLogMetric extends FileLinesMetric
{
    public const IDENTIFIER = 'nginx_error_log_new_lines';

    protected string $name = self::IDENTIFIER;
    protected string $filename = '/var/log/nginx/error.log';
}