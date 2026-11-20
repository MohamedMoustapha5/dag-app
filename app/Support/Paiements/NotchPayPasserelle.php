<?php

namespace App\Support\Paiements;

use App\Enums\IssueTransaction;
use App\Models\Paiement;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Notch Pay (https://developer.notchpay.co): Orange Money and MTN MoMo in
 * XAF.
 *
 * The payment is created with POST /payments, locked to the operator the
 * payer chose. With paiements.notchpay.paiement_direct (default), it is
 * charged right away (POST /payments/{reference}): the request reaches the
 * payer's phone and the payer waits on DAG's result page
 * (paiements.retour). Otherwise the payer is sent to the checkout page
 * Notch Pay hosts (authorization_url), then back to paiements.retour.
 *
 * Sandbox and live share the same URL: the API key decides. While
 * paiements.notchpay.environnement is "sandbox", a key that does not look
 * like a test key is refused, so no real money can be taken by mistake.
 *
 * Notch Pay posts its events (payment.complete, payment.failed…) to the
 * webhook, signed in x-notch-signature with an HMAC SHA-256 of the raw body
 * keyed with the hash key; the outcome is always re-read from the API.
 */
class NotchPayPasserelle implements PasserellePaiement
{
    public function initier(Paiement $paiement, User $payeur, ?string $telephone): InitiationPaiement
    {
        $montant = (int) round((float) $paiement->montant);
        $plafond = (int) config('paiements.notchpay.montant_maximum');

        if ($montant > $plafond) {
            throw new PasserelleIndisponible('Le paiement mobile est limité à '.number_format($plafond, 0, ',', ' ').' FCFA par transaction : contactez DAG pour régler cette facture.');
        }

        $reponse = $this->envoyer(fn (PendingRequest $http): Response => $http->post('payments', array_filter([
            'amount' => $montant,
            'currency' => config('paiements.devise'),
            'email' => $payeur->email,
            'phone' => $telephone !== null ? '+237'.$telephone : null,
            'reference' => (string) $paiement->id,
            'description' => "Facture {$paiement->facture->reference}",
            'callback' => route('paiements.retour', $paiement),
            'locked_channel' => config("paiements.notchpay.canaux.{$paiement->moyen}"),
            'locked_country' => 'CM',
        ])));

        $reference = $reponse->json('transaction.reference');
        $lien = $reponse->json('authorization_url');

        if (! is_string($reference) || $reference === '') {
            $this->journaliser('réponse d\'initialisation sans référence', $reponse);

            throw new PasserelleIndisponible('Le prestataire de paiement a renvoyé une réponse inattendue. Réessayez plus tard.');
        }

        if (config('paiements.notchpay.paiement_direct') && $telephone !== null) {
            $this->envoyer(fn (PendingRequest $http): Response => $http->post('payments/'.rawurlencode($reference), [
                'channel' => config("paiements.notchpay.canaux.{$paiement->moyen}"),
                'data' => ['phone' => '+237'.$telephone],
            ]));

            // The payer confirms on their phone and waits on DAG's result page.
            return new InitiationPaiement($reference, route('paiements.retour', $paiement));
        }

        if (! is_string($lien) || $lien === '') {
            $this->journaliser('réponse d\'initialisation sans lien', $reponse);

            throw new PasserelleIndisponible('Le prestataire de paiement a renvoyé une réponse inattendue. Réessayez plus tard.');
        }

        return new InitiationPaiement($reference, $lien);
    }

    public function lireNotification(Request $request): ?ResultatTransaction
    {
        $cle = (string) config('paiements.notchpay.cle_hachage');

        abort_if($cle === '', 403, 'Webhook non configuré.');

        $attendue = hash_hmac('sha256', $request->getContent(), $cle);

        abort_unless(hash_equals($attendue, (string) $request->header('x-notch-signature')), 403, 'Signature invalide.');

        $evenement = (string) ($request->json('type') ?? $request->json('event'));
        $reference = $request->json('data.reference');

        if (! str_starts_with($evenement, 'payment.') || ! is_string($reference) || $reference === '') {
            return null;
        }

        return $this->verifier($reference);
    }

    public function verifier(string $reference): ResultatTransaction
    {
        $reponse = $this->envoyer(fn (PendingRequest $http): Response => $http->get('payments/'.rawurlencode($reference)));

        $transaction = $reponse->json('transaction') ?? $reponse->json('data.transaction');

        if (! is_array($transaction)) {
            $this->journaliser('vérification sans transaction', $reponse);

            throw new PasserelleIndisponible('Le prestataire de paiement a renvoyé une réponse inattendue. Réessayez plus tard.');
        }

        $montant = $transaction['amount'] ?? null;
        $devise = $transaction['currency'] ?? null;

        return new ResultatTransaction(
            $reference,
            $this->issue((string) ($transaction['status'] ?? '')),
            is_numeric($montant) ? (float) $montant : null,
            is_string($devise) ? $devise : null,
        );
    }

    public function ecranLocal(): bool
    {
        return false;
    }

    /**
     * pending, processing and incomplete (paid less than asked) are still in
     * progress; an unpaid payment ends expired after 3 hours.
     */
    private function issue(string $statut): IssueTransaction
    {
        return match ($statut) {
            'complete' => IssueTransaction::Reussi,
            'failed', 'canceled', 'rejected', 'abandoned', 'expired' => IssueTransaction::Echoue,
            default => IssueTransaction::EnAttente,
        };
    }

    /**
     * Notch Pay test keys carry "test" (pk_test_…), older ones start with "sb.".
     */
    private function estCleDeTest(string $cle): bool
    {
        return str_contains(Str::lower($cle), 'test') || str_starts_with($cle, 'sb.');
    }

    /**
     * @param  callable(PendingRequest): Response  $requete
     *
     * @throws PasserelleIndisponible
     */
    private function envoyer(callable $requete): Response
    {
        $cle = (string) config('paiements.notchpay.cle_publique');

        if ($cle === '') {
            throw new PasserelleIndisponible('Le paiement en ligne n\'est pas encore configuré. Contactez DAG.');
        }

        if (config('paiements.notchpay.environnement') !== 'live' && ! $this->estCleDeTest($cle)) {
            Log::warning('Notch Pay : clé de production refusée tant que NOTCHPAY_ENV vaut sandbox.');

            throw new PasserelleIndisponible('Le paiement en ligne est en mode test mais sa clé n\'est pas une clé de test. Contactez DAG.');
        }

        $http = Http::baseUrl(rtrim((string) config('paiements.notchpay.url'), '/'))
            ->withHeaders(['Authorization' => $cle])
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('paiements.notchpay.timeout'));

        try {
            $reponse = $requete($http);
        } catch (ConnectionException $erreur) {
            Log::warning('Notch Pay injoignable', ['erreur' => $erreur->getMessage()]);

            throw new PasserelleIndisponible('Le prestataire de paiement est injoignable. Réessayez dans quelques minutes.');
        }

        if ($reponse->failed()) {
            $this->journaliser('requête refusée', $reponse);

            throw new PasserelleIndisponible('Le prestataire de paiement a refusé la demande. Vérifiez le numéro et réessayez, ou choisissez un autre moyen.');
        }

        return $reponse;
    }

    /** Logs Notch Pay's answer, never the keys sent with the request. */
    private function journaliser(string $contexte, Response $reponse): void
    {
        Log::warning("Notch Pay : {$contexte}", [
            'statut' => $reponse->status(),
            'message' => Str::limit((string) ($reponse->json('message') ?? $reponse->body()), 300),
        ]);
    }
}
