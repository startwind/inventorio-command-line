<?php

namespace Startwind\Inventorio\Collector\Frameworks\Php;

use Startwind\Inventorio\Collector\Application\WebServer\Apache\ApacheServerNameCollector;
use Startwind\Inventorio\Collector\BasicCollector;
use Startwind\Inventorio\Collector\InventoryAwareCollector;
use Startwind\Inventorio\Exec\File;

class SymfonyCollector extends BasicCollector implements InventoryAwareCollector
{
    protected string $identifier = 'FrameworkPHPSymfony';
    private array $inventory;

    public function setInventory(array $inventory): void
    {
        $this->inventory = $inventory;
    }

    public function collect(): array
    {
        if (!array_key_exists(ApacheServerNameCollector::COLLECTION_IDENTIFIER, $this->inventory)
            || !is_array($this->inventory[ApacheServerNameCollector::COLLECTION_IDENTIFIER])
        ) return [];

        $configs = $this->inventory[ApacheServerNameCollector::COLLECTION_IDENTIFIER];

        $file = File::getInstance();

        $symfonyDomains = [];

        foreach ($configs as $config) {
            $domain = $config[ApacheServerNameCollector::FIELD_SERVER_NAME];
            $documentRoot = $config[ApacheServerNameCollector::FIELD_DOCUMENT_ROOT];

            if ($file->fileExists($documentRoot . '/../composer.lock')) {
                $composerLockRaw = File::getInstance()->getContents($documentRoot . '/../composer.lock');
                $composerLock = json_decode($composerLockRaw, true);

                if (array_key_exists('packages', $composerLock) && is_array($composerLock['packages'])) {
                    foreach ($composerLock['packages'] as $package) {
                        if ($package['name'] === 'symfony/framework-bundle') {
                            $symfonyDomains[$domain] = [
                                'version' => trim($package['version'], 'v'),
                                'path' => $file->realPath($documentRoot . '/../')
                            ];
                            break;
                        }
                    }
                }
            }
        }

        return [
            'framework' => $symfonyDomains
        ];
    }
}
