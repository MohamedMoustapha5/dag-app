<?php

namespace App\Support\Paiements;

use App\Models\Paiement;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * A payment provider. The rest of the application only talks to this
 * contract, so changing provider means writing another implementation and
 * declaring it in config/paiements.php.
 *
 * initier() starts the payment on the provider's side; the final outcome
 * comes back asynchronously (webhook, read by lireNotification()) or when
 * the payer returns (verifier()), and is applied through
 * PaiementService::appliquerResultat().
 */
interface PasserellePaiement
{
    /**
     * @param  string|null  $telephone  Mobile money number (9 digits), null for a card payment.
     *
     * @throws PasserelleIndisponible
     */
    public function initier(Paiement $paiement, User $payeur, ?string $telephone): InitiationPaiement;

    /**
     * Authenticates a notification sent by the provider to the webhook and
     * returns the verified outcome of the transaction it is about, or null
     * when the notification is not about a payment outcome (ignored).
     *
     * @throws HttpException 403 when the notification is not authentic.
     * @throws PasserelleIndisponible
     */
    public function lireNotification(Request $request): ?ResultatTransaction;

    /**
     * Asks the provider for the current state of one of its transactions.
     *
     * @throws PasserelleIndisponible
     */
    public function verifier(string $reference): ResultatTransaction;

    /**
     * Whether the payer completes the payment on a DAG-hosted screen
     * (true only for the simulation) instead of on their phone / the
     * provider's page.
     */
    public function ecranLocal(): bool;
}
