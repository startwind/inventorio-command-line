<?php

include_once __DIR__ . '/../vendor/autoload.php';

use Startwind\Inventorio\Command\CollectCommand;
use Symfony\Component\Console\Application;

$application = new Application();

$application->setVersion('0.0.1');
$application->setName('Inventorio');

$application->add(new CollectCommand());

$application->run();
