<?php

namespace Startwind\Inventorio\Collector\Website\WordPress;

use Startwind\Inventorio\Collector\BasicCollector;
use Startwind\Inventorio\Collector\InventoryAwareCollector;
use Startwind\Inventorio\Exec\File;
use Startwind\Inventorio\Util\PasswordUtil;

class DatabaseCredentialCollector extends BasicCollector implements InventoryAwareCollector
{
    private array $inventory;

    public function setInventory(array $inventory): void
    {
        $this->inventory = $inventory;
    }

    public function collect(): array
    {
        if (!array_key_exists(WordPressCollector::COLLECTOR_IDENTIFIER, $this->inventory)
            || !is_array($this->inventory[WordPressCollector::COLLECTOR_IDENTIFIER])
        ) return [];

        $credentials = [];

        $wordpressSites = $this->inventory[WordPressCollector::COLLECTOR_IDENTIFIER];

        foreach ($wordpressSites as $site) {
            $configFile = File::getInstance()->getContents($site['path']);
            $credentialArray = $this->extractCredentials($configFile);

            $credentials[] = [
                'passwordStrength' => PasswordUtil::evaluateStrength($credentialArray['password']),
                'user' => $credentialArray['user'],
            ];
        }

        return $credentials;
    }

    private function extractCredentials(string $wp_config_path): ?array
    {
        if (!file_exists($wp_config_path)) {
            return null;
        }

        $content = file_get_contents($wp_config_path);
        $user = $pass = null;

        if (preg_match("/define\s*\(\s*['\"]DB_USER['\"]\s*,\s*['\"](.*?)['\"]\s*\)/", $content, $matches)) {
            $user = $matches[1];
        }

        if (preg_match("/define\s*\(\s*['\"]DB_PASSWORD['\"]\s*,\s*['\"](.*?)['\"]\s*\)/", $content, $matches)) {
            $pass = $matches[1];
        }

        if ($user !== null && $pass !== null) {
            return [
                'user' => $user,
                'password' => $pass
            ];
        }

        return null;
    }
}