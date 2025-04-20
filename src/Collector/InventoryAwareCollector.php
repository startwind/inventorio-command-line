<?php

namespace Startwind\Inventorio\Collector;

interface InventoryAwareCollector
{
    public function setInventory(array $inventory);
}
