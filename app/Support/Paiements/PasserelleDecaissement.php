<?php

namespace App\Support\Paiements;

use App\Models\Decaissement;
use App\Models\MembreEquipe;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * A provider able to send money out of DAG's balance to a mobile money
 * account (retrait). Separate from PasserellePaiement so a provider that
 * only collects payments can still be used: retraits are then refused.
 * The outcome is applied through DecaissementService::appliquerResultat().
 */
interface PasserelleDecaissement
{
    /**
     * Starts the payout and returns its state right after: most providers
     * only know the final outcome later (webhook or verifierDecaissement()).
     *
     * @throws PasserelleIndisponible
     */
    public function decaisser(Decaissement $decaissement, MembreEquipe $par): ResultatTransaction;

    /**
     * @throws PasserelleIndisponible
     */
    public function verifierDecaissement(string $reference): ResultatTransaction;

    /**
     * Authenticates a webhook notification and returns the verified outcome
     * of the payout it is about, or null when it is not about a payout.
     *
     * @throws HttpException 403 when the notification is not authentic.
     * @throws PasserelleIndisponible
     */
    public function lireNotificationDecaissement(Request $request): ?ResultatTransaction;
}
