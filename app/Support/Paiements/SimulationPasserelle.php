<?php

namespace App\Support\Paiements;

use App\Models\Paiement;
use Illuminate\Support\Str;

/**
 * Stand-in provider for development and acceptance testing: it issues a
 * fake transaction reference and lets the payer pick the outcome on a
 * local screen. It is refused in production.
 */
class SimulationPasserelle implements PasserellePaiement
{
    public function initier(Paiement $paiement, ?string $telephone): string
    {
        abort_if(app()->isProduction(), 503, 'La passerelle de simulation est désactivée en production.');

        return 'SIM-'.Str::upper(Str::random(12));
    }

    public function ecranLocal(): bool
    {
        return true;
    }
}
