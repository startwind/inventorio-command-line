<?php

namespace Startwind\Inventorio\Collector\Website\WordPress;

use Startwind\Inventorio\Collector\Application\WebServer\Apache\ApacheServerNameCollector;
use Startwind\Inventorio\Collector\BasicCollector;
use Startwind\Inventorio\Collector\InventoryAwareCollector;
use Startwind\Inventorio\Exec\File;
use Startwind\Inventorio\Exec\Runner;

class WordPressCollector extends BasicCollector implements InventoryAwareCollector
{
    public const COLLECTOR_IDENTIFIER = 'WordPress';

    protected string $identifier = self::COLLECTOR_IDENTIFIER;

    private array $inventory;

    public function setInventory(array $inventory): void
    {
        $this->inventory = $inventory;
    }

    public function collect(): array
    {
        $configs = [];

        if (array_key_exists(ApacheServerNameCollector::COLLECTION_IDENTIFIER, $this->inventory)
            && is_array($this->inventory[ApacheServerNameCollector::COLLECTION_IDENTIFIER])
        ) {
            $configs = array_merge($configs, $this->inventory[ApacheServerNameCollector::COLLECTION_IDENTIFIER]);
        }
        
        $wordPressInstallations = [];

        $runner = Runner::getInstance();

        foreach ($configs as $config) {
            $domain = $config[ApacheServerNameCollector::FIELD_SERVER_NAME];
            $documentRoot = $config[ApacheServerNameCollector::FIELD_DOCUMENT_ROOT];

            if ($runner->fileExists($documentRoot . '/wp-config.php')) {
                if (!str_ends_with($documentRoot, '/')) {
                    $documentRoot .= '/';
                }

                $wordPressInstallations[$domain] = [
                    'domain' => $domain,
                    'version' => $this->extractVersion($documentRoot),
                    'plugins' => $this->extractPlugins($documentRoot),
                    'path' => $documentRoot
                ];
            }
        }

        return $wordPressInstallations;
    }

    private function extractPlugins(string $documentRoot): array
    {
        $file = File::getInstance();

        $pluginDir = $documentRoot . 'wp-content/plugins';
        if (!$file->isDir($pluginDir)) return [];

        $plugins = array_diff($file->scanDir($pluginDir), ['.', '..']);
        $pluginArray = [];

        foreach ($plugins as $pluginFolder) {
            $path = $pluginDir . '/' . $pluginFolder;

            if (!$file->isDir($path)) continue;

            $entries = array_diff($file->scanDir($path), ['.', '..']);

            foreach ($entries as $entry) {
                $fullPath = $path . '/' . $entry;

                if ($file->isFile($fullPath) && pathinfo($entry, PATHINFO_EXTENSION) === 'php') {
                    $info = $this->parsePluginHeader($fullPath);
                    if (!empty($info['Name']) && !empty($info['Version'])) {
                        $slug = $this->deriveSlugFromHeader($info, $pluginFolder);
                        $update = $this->checkWordPressPluginUpdate($slug, $info['Version']);
                        $pluginArray[$info['Name']] = [
                            'name' => $info['Name'],
                            'slug' => $slug,
                            'version' => $info['Version'],
                            'update_available' => $update['update_available'] ?? false,
                            'latest_version' => $update['latest_version'] ?? null,
                        ];
                        break;
                    }
                }
            }
        }

        return $pluginArray;
    }

    private function parsePluginHeader(string $file): array
    {
        $headers = [
            'Name' => 'Plugin Name',
            'Version' => 'Version',
            'PluginURI' => 'Plugin URI',
        ];

        $data = File::getInstance()->getContents($file);

        $info = [];
        foreach ($headers as $key => $header) {
            if (preg_match('/' . preg_quote($header, '/') . ':\s*(.+)/i', $data, $matches)) {
                $info[$key] = trim($matches[1]);
            }
        }

        return $info;
    }

    private function deriveSlugFromHeader(array $info, string $fallback): string
    {
        // Try to extract slug from Plugin URI (e.g., https://wordpress.org/plugins/contact-form-7/)
        if (!empty($info['PluginURI'])) {
            $urlParts = parse_url($info['PluginURI']);
            if (isset($urlParts['path'])) {
                $segments = explode('/', trim($urlParts['path'], '/'));
                $lastSegment = end($segments);
                if ($lastSegment) return $lastSegment;
            }
        }

        // Fallback to directory name
        return strtolower($fallback);
    }

    private function checkWordPressPluginUpdate(string $slug, string $currentVersion): ?array
    {
        $url = "https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request[slug]=" . urlencode($slug);
        $response = @file_get_contents($url);
        if (!$response) return null;

        $pluginData = json_decode($response, true);
        if (!isset($pluginData['version'])) return null;

        $latestVersion = $pluginData['version'];

        return [
            'update_available' => version_compare($latestVersion, $currentVersion, '>'),
            'latest_version' => $latestVersion,
        ];
    }

    private function extractVersion(string $documentRoot): string
    {
        $wpVersionFile = $documentRoot . 'wp-includes/version.php';

        $version = 'unknown';

        $runner = Runner::getInstance();

        if ($runner->fileExists($wpVersionFile)) {
            $content = $runner->getFileContents($wpVersionFile);
            if (preg_match("/\\\$wp_version\s*=\s*'([^']+)'/", $content, $matches)) {
                $version = $matches[1];
            }
        }

        return $version;
    }
}
