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
 * Fapshi (https://docs.fapshi.com): Orange Money and MTN MoMo in XAF.
 *
 * By default the payer is sent to the payment page Fapshi hosts
 * (initiate-pay), then back to paiements.retour. With
 * paiements.fapshi.paiement_direct, the request is pushed straight to the
 * payer's phone (direct-pay), which Fapshi must activate for production.
 *
 * Fapshi posts the final status (SUCCESSFUL, FAILED, EXPIRED) to the
 * webhook with the shared secret in the x-wh-secret header; as Fapshi
 * recommends, the outcome is always re-read from the API.
 */
class FapshiPasserelle implements PasserellePaiement
{
    public const MONTANT_MINIMUM = 100;

    public function initier(Paiement $paiement, User $payeur, ?string $telephone): InitiationPaiement
    {
        $montant = (int) round((float) $paiement->montant);

        if ($montant < self::MONTANT_MINIMUM) {
            throw new PasserelleIndisponible('Le paiement en ligne demande un montant d\'au moins '.self::MONTANT_MINIMUM.' FCFA.');
        }

        $commun = [
            'amount' => $montant,
            'email' => $payeur->email,
            'externalId' => (string) $paiement->id,
            'message' => "Facture {$paiement->facture->reference}",
        ];

        if (config('paiements.fapshi.paiement_direct') && $telephone !== null) {
            $reponse = $this->envoyer(fn (PendingRequest $http): Response => $http->post('direct-pay', $commun + array_filter([
                'phone' => $telephone,
                'medium' => config("paiements.fapshi.mediums.{$paiement->moyen}"),
                'name' => Str::limit($payeur->name, 100, ''),
            ])));

            return new InitiationPaiement($this->reference($reponse));
        }

        $reponse = $this->envoyer(fn (PendingRequest $http): Response => $http->post('initiate-pay', $commun + [
            'redirectUrl' => route('paiements.retour', $paiement),
        ]));

        $lien = $reponse->json('link');

        if (! is_string($lien) || $lien === '') {
            $this->journaliser('réponse d\'initialisation sans lien', $reponse);

            throw new PasserelleIndisponible('Le prestataire de paiement a renvoyé une réponse inattendue. Réessayez plus tard.');
        }

        return new InitiationPaiement($this->reference($reponse), $lien);
    }

    public function lireNotification(Request $request): ?ResultatTransaction
    {
        $secret = (string) config('paiements.fapshi.webhook_secret');

        abort_if($secret === '', 403, 'Webhook non configuré.');
        abort_unless(hash_equals($secret, (string) $request->header('x-wh-secret')), 403, 'Secret de webhook invalide.');

        $reference = $request->json('transId');

        if ($request->json('transType') === 'Payout' || ! is_string($reference) || $reference === '') {
            return null;
        }

        return $this->verifier($reference);
    }

    public function verifier(string $reference): ResultatTransaction
    {
        $reponse = $this->envoyer(fn (PendingRequest $http): Response => $http->get('payment-status/'.rawurlencode($reference)));

        $montant = $reponse->json('amount');

        return new ResultatTransaction(
            $reference,
            $this->issue((string) $reponse->json('status')),
            is_numeric($montant) ? (float) $montant : null,
            config('paiements.devise'),
        );
    }

    public function ecranLocal(): bool
    {
        return false;
    }

    /**
     * Only the direct payment needs the number: otherwise the payer types
     * it on the Fapshi payment page.
     */
    public function demandeTelephone(): bool
    {
        return (bool) config('paiements.fapshi.paiement_direct');
    }

    /**
     * CREATED and PENDING are still in progress; a link left unpaid for
     * 24 hours ends EXPIRED, which closes the attempt like a failure.
     */
    private function issue(string $statut): IssueTransaction
    {
        return match ($statut) {
            'SUCCESSFUL' => IssueTransaction::Reussi,
            'FAILED', 'EXPIRED' => IssueTransaction::Echoue,
            default => IssueTransaction::EnAttente,
        };
    }

    private function reference(Response $reponse): string
    {
        $reference = $reponse->json('transId');

        if (! is_string($reference) || $reference === '') {
            $this->journaliser('réponse sans identifiant de transaction', $reponse);

            throw new PasserelleIndisponible('Le prestataire de paiement a renvoyé une réponse inattendue. Réessayez plus tard.');
        }

        return $reference;
    }

    /**
     * @param  callable(PendingRequest): Response  $requete
     *
     * @throws PasserelleIndisponible
     */
    private function envoyer(callable $requete): Response
    {
        $utilisateur = (string) config('paiements.fapshi.api_user');
        $cle = (string) config('paiements.fapshi.api_key');

        if ($utilisateur === '' || $cle === '') {
            throw new PasserelleIndisponible('Le paiement en ligne n\'est pas encore configuré. Contactez DAG.');
        }

        $http = Http::baseUrl(rtrim((string) config('paiements.fapshi.url'), '/'))
            ->withHeaders(['apiuser' => $utilisateur, 'apikey' => $cle])
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('paiements.fapshi.timeout'));

        try {
            $reponse = $requete($http);
        } catch (ConnectionException $erreur) {
            Log::warning('Fapshi injoignable', ['erreur' => $erreur->getMessage()]);

            throw new PasserelleIndisponible('Le prestataire de paiement est injoignable. Réessayez dans quelques minutes.');
        }

        if ($reponse->failed()) {
            $this->journaliser('requête refusée', $reponse);

            throw new PasserelleIndisponible('Le prestataire de paiement a refusé la demande. Vérifiez le numéro et réessayez, ou choisissez un autre moyen.');
        }

        return $reponse;
    }

    /** Logs Fapshi's answer, never the credentials sent with the request. */
    private function journaliser(string $contexte, Response $reponse): void
    {
        Log::warning("Fapshi : {$contexte}", [
            'statut' => $reponse->status(),
            'message' => Str::limit((string) ($reponse->json('message') ?? $reponse->body()), 300),
        ]);
    }
}
