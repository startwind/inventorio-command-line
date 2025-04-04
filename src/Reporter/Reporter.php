<?php

namespace Startwind\Inventorio\Reporter;

interface Reporter
{
    public function report(array $collectionData): void;
}
