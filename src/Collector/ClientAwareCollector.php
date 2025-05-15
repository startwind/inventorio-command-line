<?php

namespace Startwind\Inventorio\Collector;


use Startwind\Inventorio\Util\Client;

interface ClientAwareCollector extends Collector
{
    public function setClient(Client $client): void;
}
