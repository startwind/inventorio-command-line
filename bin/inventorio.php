<?php

include_once __DIR__ . '/../vendor/autoload.php';

use Startwind\Inventorio\Command\CollectCommand;
use Startwind\Inventorio\Command\CommandAddCommand;
use Startwind\Inventorio\Command\CommandListCommand;
use Startwind\Inventorio\Command\CommandRemoveCommand;
use Startwind\Inventorio\Command\ConfigCommand;
use Startwind\Inventorio\Command\DaemonCommand;
use Startwind\Inventorio\Command\InitCommand;
use Symfony\Component\Console\Application;

const INVENTORIO_VERSION = '##INVENTORIO_VERSION##';
const INVENTORIO_NAME = 'Inventorio';

$application = new Application();

$application->setVersion(INVENTORIO_VERSION);
$application->setName(INVENTORIO_NAME);

$application->add(new CollectCommand());
$application->add(new InitCommand());
$application->add(new DaemonCommand());
$application->add(new ConfigCommand());

// Command management
$application->add(new CommandAddCommand());
$application->add(new CommandRemoveCommand());
$application->add(new CommandListCommand());

$application->run();
