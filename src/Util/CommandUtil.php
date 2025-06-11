<?php

namespace Startwind\Inventorio\Util;

abstract class CommandUtil
{
    function splitCommands(string $input): array {
        $lines = explode("\n", $input);
        $commands = [];
        $current = '';

        $openQuotes = false;
        $openBraces = 0;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' && $current === '') {
                continue; // Leere Zeile vor erstem Befehl ignorieren
            }

            $current .= ($current === '' ? '' : "\n") . $line;

            // Prüfen auf offene/geschlossene Anführungszeichen
            $quoteCount = substr_count($line, '"');
            if ($quoteCount % 2 !== 0) {
                $openQuotes = !$openQuotes;
            }

            // Zählen von offenen/geschlossenen Klammern (für {...})
            $openBraces += substr_count($line, '{');
            $openBraces -= substr_count($line, '}');

            // Nur speichern, wenn keine offenen Blöcke mehr vorhanden sind
            if (!$openQuotes && $openBraces <= 0 && trim($line) !== '') {
                $commands[] = trim($current);
                $current = '';
            }
        }

        if (trim($current) !== '') {
            $commands[] = trim($current); // Rest hinzufügen
        }

        return $commands;
    }

}