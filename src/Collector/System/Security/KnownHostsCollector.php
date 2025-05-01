<?php

namespace Startwind\Inventorio\Collector\System\Security;

use Startwind\Inventorio\Collector\Collector;

class KnownHostsCollector implements Collector
{
    public function getIdentifier(): string
    {
        return 'known-hosts-all-users';
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
            if ((int)$uid < 1000 || !is_dir($homeDirectory)) {
                continue;
            }

            $knownHostsPath = $homeDirectory . '/.ssh/known_hosts';

            // If known_hosts exists, parse it
            if (!file_exists($knownHostsPath)) {
                continue;
            }

            $entries = $this->parseKnownHostsFile($knownHostsPath, $username);

            // Merge entries into the final result list
            $results = array_merge($results, $entries);
        }

        return $results;
    }

    /**
     * Parse a known_hosts file and return structured entries including username.
     *
     * @param string $filePath Path to the known_hosts file
     * @param string $username The user who owns the file
     * @return array List of structured known host entries
     */
    private function parseKnownHostsFile(string $filePath, string $username): array
    {
        $entries = [];

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $parts = preg_split('/\s+/', $line);

            // Process only lines with at least host, key type, and key
            if (count($parts) >= 3) {
                $entries[] = [
                    'user' => $username,
                    'host' => $parts[0],
                    'key_type' => $parts[1],
                    'key' => $parts[2],
                    'comment' => $parts[3] ?? null
                ];
            }
        }

        return $entries;
    }
}
