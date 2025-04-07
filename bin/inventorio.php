<?php

include_once __DIR__ . '/../vendor/autoload.php';

use Startwind\Inventorio\Command\CollectCommand;
use Startwind\Inventorio\Command\InitCommand;
use Symfony\Component\Console\Application;

const INVENTORIO_VERSION = '##INVENTORIO_VERSION##';

$application = new Application();

$application->setVersion(INVENTORIO_VERSION);
$application->setName('Inventorio');

$application->add(new CollectCommand());
$application->add(new InitCommand());

$application->run();
