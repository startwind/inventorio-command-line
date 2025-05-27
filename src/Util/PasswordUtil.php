<?php

namespace Startwind\Inventorio\Util;

abstract class PasswordUtil
{
    static public function evaluateStrength(string $password): array
    {
        $rules = [];

        if (strlen($password) >= 12) {
            $rules['passwordTooShort'] = false;
        } else {
            $rules['passwordTooShort'] = true;
        }

        if (preg_match('/[a-z]/', $password)) {
            $rules['onlyLowercase'] = true;
        } else {
            $rules['onlyLowercase'] = false;
        }

        if (preg_match('/[A-Z]/', $password)) {
            $rules['onlyUppercase'] = true;
        } else {
            $rules['onlyUppercase'] = false;
        }

        if (preg_match('/[0-9]/', $password)) {
            $rules['onlyNumbers'] = true;
        } else {
            $rules['onlyNumbers'] = false;
        }

        if (preg_match('/[\W_]/', $password)) {
            $rules['symbolNotIncluded'] = true;
        } else {
            $rules['symbolNotIncluded'] = false;
        }

        /*
        $types = 0;
        $types += preg_match('/[a-z]/', $password);
        $types += preg_match('/[A-Z]/', $password);
        $types += preg_match('/[0-9]/', $password);
        $types += preg_match('/[\W_]/', $password);
        if ($types >= 3) {
            $score += $variationWeight;
        }
        */

        if (preg_match('/(.)\1{3,}/', $password)) {
            $rules['passwordRepeating'] = true;
        } else {
            $rules['passwordRepeating'] = false;
        }

        $common = ['123456', 'password', 'qwerty', 'admin', 'letmein'];
        if (in_array(strtolower($password), $common)) {
            $rules['commonPassword'] = true;
        } else {
            $rules['commonPassword'] = false;
        }

        return $rules;
    }
}