<?php

namespace App\Enums;

/**
 * Courtesy title of a DAG team member, shown before their name on the
 * public pages (« Mme Mandou », « Mr Pascal »).
 */
enum Civilite: string
{
    case Monsieur = 'M';
    case Madame = 'MME';

    public function label(): string
    {
        return match ($this) {
            self::Monsieur => 'Mr',
            self::Madame => 'Mme',
        };
    }
}
