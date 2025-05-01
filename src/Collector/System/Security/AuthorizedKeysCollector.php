<?php

namespace Startwind\Inventorio\Collector\System\Security;

use Startwind\Inventorio\Collector\Collector;

class AuthorizedKeysCollector implements Collector
{
    public function getIdentifier(): string
    {
        return 'SystemSecurityAuthorizedKeys';
    }

    public function collect(): array
    {
        $results = [];

        // Read all user accounts from /etc/passwd
        $passwdLines = file('/etc/passwd', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($passwdLines as $line) {
            $parts = explode(':', $line);
            if (count($parts) < 6) {
                continue;
            }

            // Extract username, UID, and home directory
            [$username, , $uid, , , $homeDirectory] = array_slice($parts, 0, 6);

            // Only consider regular users (UID >= 1000) with a valid home directory
            if (!is_dir($homeDirectory)) {
                continue;
            }

            $authorizedKeysPath = $homeDirectory . '/.ssh/authorized_keys';

            // If authorized_keys exists, parse it
            if (!file_exists($authorizedKeysPath)) {
                continue;
            }

            $entries = $this->parseAuthorizedKeysFile($authorizedKeysPath, $username);

            // Merge entries into the final result list
            $results = array_merge($results, $entries);
        }

        return $results;
    }

    /**
     * Parse an authorized_keys file and return structured entries including username.
     *
     * @param string $filePath Path to the authorized_keys file
     * @param string $username The user who owns the file
     * @return array List of structured authorized key entries
     */
    private function parseAuthorizedKeysFile(string $filePath, string $username): array
    {
        $entries = [];

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Some lines may include options before the key, like: command="..." ssh-rsa AAAA...
            if (preg_match('/^(ssh-(rsa|ed25519|dss)|ecdsa-[^\s]+)\s+([A-Za-z0-9+\/=]+)(\s+(.*))?$/', $line, $matches)) {
                $entries[] = [
                    'user' => $username,
                    'key_type' => $matches[1],
                    'key' => $matches[3],
                    'comment' => $matches[5] ?? null
                ];
            }
        }

        return $entries;
    }
}
