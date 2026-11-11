<?php

namespace App\Support\Paiements;

use App\Enums\IssueTransaction;
use App\Models\Decaissement;
use App\Models\MembreEquipe;
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
 * Moneroo (https://docs.moneroo.io): the payer is redirected to the
 * checkout page Moneroo hosts, then sent back to paiements.retour.
 * Moneroo also posts its events to the webhook, signed with HMAC SHA-256
 * of the raw body in the X-Moneroo-Signature header. Its payload is
 * deliberately partial, so the outcome is always re-read from the API.
 * Retraits go out as Moneroo payouts to an Orange Money / MTN number.
 */
class MonerooPasserelle implements PasserelleDecaissement, PasserellePaiement
{
    public function initier(Paiement $paiement, User $payeur, ?string $telephone): InitiationPaiement
    {
        $facture = $paiement->facture;
        [$prenom, $nom] = $this->prenomEtNom($payeur->name);

        $reponse = $this->envoyer(fn (PendingRequest $http): Response => $http->post('payments/initialize', array_filter([
            'amount' => (int) round((float) $paiement->montant),
            'currency' => config('paiements.devise'),
            'description' => "Facture {$facture->reference}",
            'return_url' => route('paiements.retour', $paiement),
            'customer' => array_filter([
                'email' => $payeur->email,
                'first_name' => $prenom,
                'last_name' => $nom,
                'phone' => $telephone !== null ? '+237'.$telephone : null,
                'country' => 'CM',
            ]),
            'metadata' => ['paiement_id' => (string) $paiement->id, 'facture' => (string) $facture->reference],
            'methods' => config("paiements.moneroo.methodes.{$paiement->moyen}"),
        ])));

        $reference = $reponse->json('data.id');
        $url = $reponse->json('data.checkout_url');

        if (! is_string($reference) || ! is_string($url)) {
            $this->journaliser('réponse d\'initialisation inattendue', $reponse);

            throw new PasserelleIndisponible('Le prestataire de paiement a renvoyé une réponse inattendue. Réessayez plus tard.');
        }

        return new InitiationPaiement($reference, $url);
    }

    public function lireNotification(Request $request): ?ResultatTransaction
    {
        WebhookSignature::verifier($request, 'X-Moneroo-Signature');

        $evenement = (string) $request->json('event');
        $reference = $request->json('data.id');

        if (! str_starts_with($evenement, 'payment.') || ! is_string($reference) || $reference === '') {
            return null;
        }

        return $this->verifier($reference);
    }

    public function verifier(string $reference): ResultatTransaction
    {
        $reponse = $this->envoyer(fn (PendingRequest $http): Response => $http->get('payments/'.rawurlencode($reference).'/verify'));

        $montant = $reponse->json('data.amount');
        $devise = $reponse->json('data.currency.code') ?? $reponse->json('data.currency');

        return new ResultatTransaction(
            $reference,
            $this->issue((string) $reponse->json('data.status')),
            is_numeric($montant) ? (float) $montant : null,
            is_string($devise) ? $devise : null,
        );
    }

    public function ecranLocal(): bool
    {
        return false;
    }

    /**
     * Moneroo only queues the payout here: its outcome arrives through the
     * payout.* webhook events or verifierDecaissement().
     */
    public function decaisser(Decaissement $decaissement, MembreEquipe $par): ResultatTransaction
    {
        $methode = config("paiements.moneroo.methodes_decaissement.{$decaissement->moyen}");

        if (! is_string($methode)) {
            throw new PasserelleIndisponible('Ce moyen de retrait n\'est pas disponible chez le prestataire.');
        }

        [$prenom, $nom] = $this->prenomEtNom($decaissement->beneficiaire);

        $reponse = $this->envoyer(fn (PendingRequest $http): Response => $http->post('payouts/initialize', [
            'amount' => (int) round((float) $decaissement->montant),
            'currency' => config('paiements.devise'),
            'description' => Str::limit($decaissement->motif, 250),
            'method' => $methode,
            'customer' => ['email' => $par->email, 'first_name' => $prenom, 'last_name' => $nom, 'country' => 'CM'],
            'recipient' => ['msisdn' => '237'.$decaissement->telephone],
            'metadata' => ['decaissement_id' => (string) $decaissement->id],
        ]));

        $reference = $reponse->json('data.id');

        if (! is_string($reference) || $reference === '') {
            $this->journaliser('réponse de retrait inattendue', $reponse);

            throw new PasserelleIndisponible('Le prestataire a renvoyé une réponse inattendue : vérifiez le retrait sur votre tableau de bord Moneroo avant de réessayer.');
        }

        return new ResultatTransaction($reference, IssueTransaction::EnAttente);
    }

    public function verifierDecaissement(string $reference): ResultatTransaction
    {
        $reponse = $this->envoyer(fn (PendingRequest $http): Response => $http->get('payouts/'.rawurlencode($reference).'/verify'));

        $montant = $reponse->json('data.amount');
        $devise = $reponse->json('data.currency.code') ?? $reponse->json('data.currency');
        $motif = $reponse->json('data.disburse.failure_message') ?? $reponse->json('data.disburse.gateway.transaction_failure_message');

        return new ResultatTransaction(
            $reference,
            $this->issue((string) $reponse->json('data.status')),
            is_numeric($montant) ? (float) $montant : null,
            is_string($devise) ? $devise : null,
            is_string($motif) ? Str::limit($motif, 250) : null,
        );
    }

    public function lireNotificationDecaissement(Request $request): ?ResultatTransaction
    {
        WebhookSignature::verifier($request, 'X-Moneroo-Signature');

        $reference = $request->json('data.id');

        if (! str_starts_with((string) $request->json('event'), 'payout.') || ! is_string($reference) || $reference === '') {
            return null;
        }

        return $this->verifierDecaissement($reference);
    }

    /**
     * Payments and payouts share the same status words; "initiated" and
     * "pending" are still in progress.
     */
    private function issue(string $statut): IssueTransaction
    {
        return match ($statut) {
            'success' => IssueTransaction::Reussi,
            'failed', 'cancelled' => IssueTransaction::Echoue,
            default => IssueTransaction::EnAttente,
        };
    }

    /**
     * @param  callable(PendingRequest): Response  $requete
     *
     * @throws PasserelleIndisponible
     */
    private function envoyer(callable $requete): Response
    {
        $cle = (string) config('paiements.moneroo.cle_secrete');

        if ($cle === '') {
            throw new PasserelleIndisponible('Le paiement en ligne n\'est pas encore configuré. Contactez DAG.');
        }

        $http = Http::baseUrl((string) config('paiements.moneroo.url'))
            ->withToken($cle)
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('paiements.moneroo.timeout'));

        try {
            $reponse = $requete($http);
        } catch (ConnectionException $erreur) {
            Log::warning('Moneroo injoignable', ['erreur' => $erreur->getMessage()]);

            throw new PasserelleIndisponible('Le prestataire de paiement est injoignable. Réessayez dans quelques minutes.');
        }

        if ($reponse->failed()) {
            $this->journaliser('requête refusée', $reponse);

            throw new PasserelleIndisponible('Le prestataire de paiement a refusé la demande. Réessayez plus tard ou choisissez un autre moyen.');
        }

        return $reponse;
    }

    private function journaliser(string $contexte, Response $reponse): void
    {
        Log::warning("Moneroo : {$contexte}", ['statut' => $reponse->status(), 'corps' => Str::limit($reponse->body(), 2000)]);
    }

    /**
     * Moneroo requires both names: a single-word name is used for both.
     *
     * @return array{0: string, 1: string}
     */
    private function prenomEtNom(string $nomComplet): array
    {
        $parties = preg_split('/\s+/', trim($nomComplet), 2) ?: [];
        $prenom = $parties[0] ?? '';

        if ($prenom === '') {
            $prenom = 'Client';
        }

        return [$prenom, $parties[1] ?? $prenom];
    }
}
