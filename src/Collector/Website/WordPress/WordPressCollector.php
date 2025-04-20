<?php

namespace Startwind\Inventorio\Collector\Website\WordPress;

use Startwind\Inventorio\Collector\Application\WebServer\Apache\ApacheServerNameCollector;
use Startwind\Inventorio\Collector\BasicCollector;
use Startwind\Inventorio\Collector\InventoryAwareCollector;

class WordPressCollector extends BasicCollector implements InventoryAwareCollector
{
    protected string $identifier = "WordPress";

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

        $wordPressInstallations = [];

        foreach ($configs as $config) {
            $domain = $config[ApacheServerNameCollector::FIELD_SERVER_NAME];
            $documentRoot = $config[ApacheServerNameCollector::FIELD_DOCUMENT_ROOT];

            if (file_exists($documentRoot . '/wp-config.php')) {

                if (!str_ends_with($documentRoot, '/')) $documentRoot = $documentRoot . '/';

                $wpVersionFile = $documentRoot . 'wp-includes/version.php';

                $version = 'unknown';

                if (file_exists($wpVersionFile)) {
                    $content = file_get_contents($wpVersionFile);
                    if (preg_match("/\\\$wp_version\s*=\s*'([^']+)'/", $content, $matches)) {
                        $version = $matches[1];
                    }
                }

                $wordPressInstallations[] = [
                    'domain' => $domain,
                    'version' => $version
                ];
            }
        }

        return $wordPressInstallations;
    }

}
