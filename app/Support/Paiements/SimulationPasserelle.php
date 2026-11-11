<?php

namespace App\Support\Paiements;

use App\Enums\IssueTransaction;
use App\Models\Decaissement;
use App\Models\MembreEquipe;
use App\Models\Paiement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Stand-in provider for development and acceptance testing: it issues a
 * fake transaction reference and lets the payer pick the outcome on a
 * local screen. It only runs in the environments listed below: anywhere
 * else (production, but also a publicly reachable staging) any payer could
 * settle their own facture for free.
 *
 * Its webhook takes the generic JSON {"reference": "...", "statut":
 * "success"|"failed", "montant": 12345} signed with HMAC SHA-256 of the
 * body in the X-Signature header.
 */
class SimulationPasserelle implements PasserelleDecaissement, PasserellePaiement
{
    /** @var list<string> */
    public const ENVIRONNEMENTS_AUTORISES = ['local', 'testing'];

    public static function autorisee(): bool
    {
        return app()->environment(self::ENVIRONNEMENTS_AUTORISES);
    }

    public function initier(Paiement $paiement, User $payeur, ?string $telephone): InitiationPaiement
    {
        abort_unless(self::autorisee(), 503, 'La passerelle de simulation est désactivée dans cet environnement.');

        return new InitiationPaiement('SIM-'.Str::upper(Str::random(12)));
    }

    public function lireNotification(Request $request): ?ResultatTransaction
    {
        WebhookSignature::verifier($request, 'X-Signature');

        $data = $request->validate([
            'reference' => ['required', 'string'],
            'statut' => ['required', 'in:success,failed'],
            'montant' => ['required', 'numeric'],
        ]);

        return new ResultatTransaction(
            $data['reference'],
            $data['statut'] === 'success' ? IssueTransaction::Reussi : IssueTransaction::Echoue,
            (float) $data['montant'],
        );
    }

    /**
     * The simulation keeps no state of its own: the outcome is only known
     * once the payer picks it on the local screen.
     */
    public function verifier(string $reference): ResultatTransaction
    {
        return new ResultatTransaction($reference, IssueTransaction::EnAttente);
    }

    public function ecranLocal(): bool
    {
        return true;
    }

    /**
     * A simulated retrait succeeds at once: there is no money to move.
     */
    public function decaisser(Decaissement $decaissement, MembreEquipe $par): ResultatTransaction
    {
        abort_unless(self::autorisee(), 503, 'La passerelle de simulation est désactivée dans cet environnement.');

        return new ResultatTransaction('SIM-PO-'.Str::upper(Str::random(10)), IssueTransaction::Reussi, (float) $decaissement->montant);
    }

    public function verifierDecaissement(string $reference): ResultatTransaction
    {
        return new ResultatTransaction($reference, IssueTransaction::EnAttente);
    }

    public function lireNotificationDecaissement(Request $request): ?ResultatTransaction
    {
        return null;
    }
}
