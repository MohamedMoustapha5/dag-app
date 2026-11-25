<?php

namespace App\Support\Paiements;

/**
 * Cameroonian mobile numbers, as used for mobile money payments and
 * retraits.
 */
class TelephoneMobile
{
    /**
     * Normalises a Cameroonian mobile number to 9 digits starting with 6,
     * or returns null when it is not one.
     */
    public static function normaliser(string $saisie): ?string
    {
        $chiffres = preg_replace('/\D+/', '', $saisie) ?? '';

        if (str_starts_with($chiffres, '237') && strlen($chiffres) === 12) {
            $chiffres = substr($chiffres, 3);
        }

        return preg_match('/^6\d{8}$/', $chiffres) === 1 ? $chiffres : null;
    }
}
