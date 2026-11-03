<?php

namespace App\Support\Paiements;

use App\Models\Paiement;

/**
 * A payment provider. initier() starts the payment on the provider's side
 * and returns the provider's transaction reference; the final outcome
 * comes back asynchronously (webhook) and is applied through
 * PaiementService::confirmer() / echouer().
 */
interface PasserellePaiement
{
    /**
     * @param  string|null  $telephone  Mobile money number, null for a card payment.
     */
    public function initier(Paiement $paiement, ?string $telephone): string;

    /**
     * Whether the payer completes the payment on a DAG-hosted screen
     * (true only for the simulation) instead of on their phone / the
     * provider's page.
     */
    public function ecranLocal(): bool;
}
