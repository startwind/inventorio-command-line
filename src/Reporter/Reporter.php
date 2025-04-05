<?php

namespace Startwind\Inventorio\Reporter;

interface Reporter
{
    /**
     * Reporters are used to handle the collected data.
     *
     * @param array $collectionData
     * @return void
     */
    public function report(array $collectionData): void;
}
