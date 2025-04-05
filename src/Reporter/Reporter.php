<?php

namespace Startwind\Inventorio\Reporter;

interface Reporter
{
    /**
     * Reporters are used to handle the collected data.
     */
    public function report(array $collectionData): void;
}
