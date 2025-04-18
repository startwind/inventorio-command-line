<?php

namespace Startwind\Inventorio\Collector\System;

use Startwind\Inventorio\Collector\BasicCollector;

class UserCollector extends BasicCollector
{
    private const PASSWORD_FILE = '/etc/passwd';

    protected string $identifier = 'SystemUser';

    public function collect(): array
    {
        $passwdFile = self::PASSWORD_FILE;

        if (!file_exists($passwdFile) || !is_readable($passwdFile)) {
            return [];
        }

        $users = [];

        foreach (file($passwdFile) as $line) {
            $parts = explode(':', trim($line));

            if (count($parts) < 7) {
                continue;
            }

            list($username, $password, $uid, $gid, $comment, $home, $shell) = $parts;

            $users[] = [
                'username' => $username,
                'uid' => (int)$uid,
                'gid' => (int)$gid,
                'comment' => $comment,
                'home' => $home,
                'shell' => $shell,
            ];
        }

        return ['users' => $users];
    }

}
