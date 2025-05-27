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
            $rules['noLowercase'] = false;
        } else {
            $rules['noLowercase'] = true;
        }

        if (preg_match('/[A-Z]/', $password)) {
            $rules['noUppercase'] = false;
        } else {
            $rules['noUppercase'] = true;
        }

        if (preg_match('/[0-9]/', $password)) {
            $rules['noNumber'] = false;
        } else {
            $rules['noNumber'] = true;
        }

        if (preg_match('/[\W_]/', $password)) {
            $rules['noSymbol'] = false;
        } else {
            $rules['noSymbol'] = true;
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