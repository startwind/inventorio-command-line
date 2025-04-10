<?php

namespace Startwind\Inventorio\Collector\Application\ProgrammingLanguage;

use Startwind\Inventorio\Collector\Collector;

class PhpCollector implements Collector
{
    protected const COLLECTION_IDENTIFIER = 'PHP';

    /**
     * @inheritDoc
     */
    public function getIdentifier(): string
    {
        return self::COLLECTION_IDENTIFIER;
    }

    /**
     * @inheritDoc
     */
    public function collect(): array
    {
        exec('pgrep -a php-fpm', $output);

        $fpm = !empty($output);

        return [
            'versions' => [
                PHP_VERSION
            ],
            'modules' => get_loaded_extensions(),
            'fpm' => $fpm
        ];
    }
}
