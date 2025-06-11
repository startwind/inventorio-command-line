<?php

namespace Startwind\Inventorio\Util;

abstract class CommandUtil
{
    public static function splitCommands(string $input): array
    {
        $lines = explode("\n", $input);
        $commands = [];
        $current = '';

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            if (preg_match('/^\s+/', $line)) {
                $current .= "\n" . $line;
            } else {
                if ($current !== '') {
                    $commands[] = trim($current);
                }
                $current = $line;
            }
        }

        if ($current !== '') {
            $commands[] = trim($current);
        }

        return $commands;
    }

}