<?php

namespace Startwind\Inventorio\Collector;

interface InventoryAwareCollector extends Collector
{
    public function setInventory(array $inventory): void;
}
