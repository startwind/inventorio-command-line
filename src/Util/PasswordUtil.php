<?php

namespace Startwind\Inventorio\Util;

abstract class PasswordUtil
{
   static public function evaluateStrength(string $password): int
    {
        $score = 0;
        $maxScore = 100;

        // Gewichtungen (anpassbar)
        $lengthWeight = 30;
        $lowercaseWeight = 10;
        $uppercaseWeight = 10;
        $numberWeight = 15;
        $symbolWeight = 15;
        $variationWeight = 10;
        $commonPenalty = -30;

        $length = strlen($password);

        // Mindestlänge
        if ($length >= 12) {
            $score += $lengthWeight;
        } elseif ($length >= 8) {
            $score += $lengthWeight / 2;
        }

        // Kleinbuchstaben
        if (preg_match('/[a-z]/', $password)) {
            $score += $lowercaseWeight;
        }

        // Großbuchstaben
        if (preg_match('/[A-Z]/', $password)) {
            $score += $uppercaseWeight;
        }

        // Zahlen
        if (preg_match('/[0-9]/', $password)) {
            $score += $numberWeight;
        }

        // Sonderzeichen
        if (preg_match('/[\W_]/', $password)) {
            $score += $symbolWeight;
        }

        // Zeichenvielfalt (mind. 3 Kategorien)
        $types = 0;
        $types += preg_match('/[a-z]/', $password);
        $types += preg_match('/[A-Z]/', $password);
        $types += preg_match('/[0-9]/', $password);
        $types += preg_match('/[\W_]/', $password);
        if ($types >= 3) {
            $score += $variationWeight;
        }

        // Wiederholungen
        if (preg_match('/(.)\1{3,}/', $password)) {
            $score -= 10;
        }

        // Häufige oder unsichere Passwörter
        $common = ['123456', 'password', 'qwerty', 'admin', 'letmein'];
        if (in_array(strtolower($password), $common)) {
            $score += $commonPenalty;
        }

        // Begrenzung des Scores
        $score = max(0, min($score, $maxScore));

        // Bewertung
        if ($score >= 80) {
            $rating = 'strong';
        } elseif ($score >= 50) {
            $rating = 'medium';
        } else {
            $rating = 'weak';
        }

        return $score;
    }
}