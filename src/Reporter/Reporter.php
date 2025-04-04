<?php

namespace Startwind\Inventorio\Reporter;

interface Reporter
{
    public function report(array $collectionData, string $serverId): void;
}
