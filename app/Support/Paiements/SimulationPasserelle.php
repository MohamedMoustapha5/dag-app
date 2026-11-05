<?php

namespace App\Support\Paiements;

use App\Models\Paiement;
use Illuminate\Support\Str;

/**
 * Stand-in provider for development and acceptance testing: it issues a
 * fake transaction reference and lets the payer pick the outcome on a
 * local screen. It only runs in the environments listed below: anywhere
 * else (production, but also a publicly reachable staging) any payer could
 * settle their own facture for free.
 */
class SimulationPasserelle implements PasserellePaiement
{
    /** @var list<string> */
    public const ENVIRONNEMENTS_AUTORISES = ['local', 'testing'];

    public static function autorisee(): bool
    {
        return app()->environment(self::ENVIRONNEMENTS_AUTORISES);
    }

    public function initier(Paiement $paiement, ?string $telephone): string
    {
        abort_unless(self::autorisee(), 503, 'La passerelle de simulation est désactivée dans cet environnement.');

        return 'SIM-'.Str::upper(Str::random(12));
    }

    public function ecranLocal(): bool
    {
        return true;
    }
}
