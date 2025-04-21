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

                $wordPressInstallations[$domain] = [
                    'domain' => $domain,
                    'version' => $this->extractVersion($documentRoot),
                    'plugins' => $this->extractPlugins($documentRoot)
                ];
            }
        }

        return $wordPressInstallations;
    }

    private function extractPlugins(string $documentRoot): array
    {
        $pluginDir = $documentRoot . '/wp-content/plugins';
        $plugins = array_diff(scandir($pluginDir), ['.', '..']);

        $pluginArray = [];

        foreach ($plugins as $pluginFolder) {
            $path = $pluginDir . '/' . $pluginFolder;

            if (is_dir($path)) {
                $phpFiles = glob("$path/*.php");
                foreach ($phpFiles as $phpFile) {
                    $info = $this->parsePluginHeader($phpFile);
                    if (!empty($info['Name'])) {
                        $pluginArray[] = [
                            'name' => $info['Name'],
                            'version' => $info['Version']
                        ];
                        break;
                    }
                }
            } elseif (is_file($path) && substr($pluginFolder, -4) === '.php') {
                $info = $this->parsePluginHeader($path);
                if (!empty($info['Name'])) {
                    $pluginArray[] = [
                        'name' => $info['Name'],
                        'version' => $info['Version']
                    ];
                }
            }
        }

        return $pluginArray;
    }

    private function parsePluginHeader($file): array
    {
        $headers = [
            'Name' => 'Plugin Name',
            'Version' => 'Version',
        ];

        $fp = fopen($file, 'r');
        if (!$fp) return [];

        $data = fread($fp, 8192);
        fclose($fp);

        $info = [];
        foreach ($headers as $key => $header) {
            if (preg_match('/' . preg_quote($header, '/') . ':\s*(.+)/i', $data, $matches)) {
                $info[$key] = trim($matches[1]);
            }
        }

        return $info;
    }

    private function extractVersion(string $documentRoot): string
    {
        $wpVersionFile = $documentRoot . 'wp-includes/version.php';

        $version = 'unknown';

        if (file_exists($wpVersionFile)) {
            $content = file_get_contents($wpVersionFile);
            if (preg_match("/\\\$wp_version\s*=\s*'([^']+)'/", $content, $matches)) {
                $version = $matches[1];
            }
        }

        return $version;
    }
}
