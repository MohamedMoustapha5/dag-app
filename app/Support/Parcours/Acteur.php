<?php

namespace App\Support\Parcours;

/**
 * Who acts on a step of a procedure, and how to name them for whoever is
 * looking: the client (or apprenant) sees "Vous" and "DAG", the team sees
 * "Client" (or "Apprenant") and "DAG".
 */
final class Acteur
{
    public const CLIENT = 'client';

    public const DAG = 'dag';

    /**
     * Keeps an amount and its currency on one line ("20 000 FCFA").
     */
    public const ESPACE_INSECABLE = "\u{00A0}";

    /**
     * @param  'client'|'admin'  $audience
     */
    public static function libelle(string $acteur, string $audience, string $nomClient = 'Client'): string
    {
        if ($acteur === self::DAG) {
            return 'DAG';
        }

        return $audience === 'client' ? 'Vous' : $nomClient;
    }

    /**
     * Whether the step is for the person looking at it.
     *
     * @param  'client'|'admin'  $audience
     */
    public static function estPour(string $acteur, string $audience): bool
    {
        return ($acteur === self::CLIENT) === ($audience === 'client');
    }
}
