<?php

namespace Startwind\Inventorio\Collector\System;

use Startwind\Inventorio\Collector\BasicCollector;
use Startwind\Inventorio\Exec\File;
use Startwind\Inventorio\Exec\Runner;

class UserCollector extends BasicCollector
{
    private const PASSWORD_FILE = '/etc/passwd';

    protected string $identifier = 'SystemUser';

    public function collect(): array
    {
        $passwdFile = self::PASSWORD_FILE;

        $runner = Runner::getInstance();

        if (!$runner->fileExists($passwdFile) || !File::getInstance()->isReadable($passwdFile)) {
            return [];
        }

        $users = [];

        foreach (File::getInstance()->getContents($passwdFile, true) as $line) {
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
