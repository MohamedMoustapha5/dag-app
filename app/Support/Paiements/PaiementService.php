<?php

namespace App\Support\Paiements;

use App\Enums\Action;
use App\Enums\Donnee;
use App\Models\ActivityLog;
use App\Models\Demande;
use App\Models\Facture;
use App\Models\MembreEquipe;
use App\Models\Paiement;
use App\Notifications\PaiementConfirmeNotification;
use App\Support\ContratRedaction;
use App\Support\Notifications\Notifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Single place where money is applied to a facture: manual payments
 * recorded by DAG staff, and online payments confirmed by the provider
 * (or by staff) all go through confirmer()/enregistrerManuel().
 */
class PaiementService
{
    public function passerelle(): PasserellePaiement
    {
        $classe = config('paiements.passerelles.'.config('paiements.passerelle'));

        abort_if($classe === null, 503, 'Passerelle de paiement non configurée.');

        return app($classe);
    }

    /**
     * Issues the facture a client pays for a demande once its devis is
     * sent. Idempotent: an existing unpaid facture only has its amount
     * adjusted, and a facture that already received money is left alone.
     */
    public function emettreFacturePourDevis(Demande $demande, float $montant): Facture
    {
        $facture = $demande->facture;

        if ($facture === null) {
            return Facture::create([
                'type' => 'SERVICE',
                'client_id' => $demande->client_id,
                'entreprise_id' => $demande->entreprise_id,
                'demande_id' => $demande->id,
                'montant' => $montant,
                'montant_paye' => 0,
                'statut' => 'EN_ATTENTE',
            ]);
        }

        if ((float) $facture->montant_paye <= 0) {
            $facture->update(['montant' => $montant]);
        }

        return $facture;
    }

    public function soldeRestant(Facture $facture): float
    {
        return max(0.0, (float) $facture->montant - (float) $facture->montant_paye);
    }

    /**
     * Whether an online payment was started recently and is still waiting
     * for its confirmation.
     */
    public function paiementEnAttente(Facture $facture): bool
    {
        return $facture->paiements()
            ->where('statut', 'EN_ATTENTE')
            ->where('created_at', '>', now()->subMinutes((int) config('paiements.delai_attente_minutes')))
            ->exists();
    }

    /**
     * The payment methods offered to payers, in display order.
     *
     * @return list<array{value: string, label: string, mobile: bool}>
     */
    public function moyens(): array
    {
        $moyens = [];

        foreach (config('paiements.moyens') as $valeur => $moyen) {
            $moyens[] = ['value' => $valeur, 'label' => $moyen['label'], 'mobile' => $moyen['mobile']];
        }

        return $moyens;
    }

    /**
     * Starts an online payment of the whole remaining balance.
     *
     * @throws ValidationException
     */
    public function initier(Facture $facture, string $moyen, ?string $telephone): Paiement
    {
        if (! in_array($facture->statut, ['EN_ATTENTE', 'PARTIELLEMENT_PAYEE'], true) || $this->soldeRestant($facture) <= 0) {
            throw ValidationException::withMessages(['moyen' => 'Cette facture est déjà réglée.']);
        }

        if ($this->paiementEnAttente($facture)) {
            throw ValidationException::withMessages([
                'moyen' => 'Un paiement est déjà en attente de confirmation pour cette facture.',
            ]);
        }

        $paiement = Paiement::create([
            'facture_id' => $facture->id,
            'montant' => $this->soldeRestant($facture),
            'moyen' => $moyen,
            'statut' => 'EN_ATTENTE',
        ]);

        $paiement->update([
            'reference_transaction' => $this->passerelle()->initier($paiement, $telephone),
        ]);

        return $paiement;
    }

    /**
     * Idempotent: only a paiement still EN_ATTENTE can be confirmed.
     */
    public function confirmer(Paiement $paiement, ?MembreEquipe $par = null): void
    {
        DB::transaction(function () use ($paiement, $par): void {
            $verrouille = Paiement::where('id', $paiement->id)->lockForUpdate()->firstOrFail();

            if ($verrouille->statut !== 'EN_ATTENTE') {
                return;
            }

            $verrouille->update([
                'statut' => 'CONFIRME',
                'date_paiement' => now(),
                'valide_par_id' => $par?->id,
            ]);

            $this->appliquerAFacture($verrouille->facture, (float) $verrouille->montant, $verrouille->moyen);
            $this->notifierPaiement($verrouille->facture, (float) $verrouille->montant);

            ActivityLog::record(
                $par,
                $par === null ? 'paiement en ligne confirmé' : 'a confirmé un paiement',
                $verrouille->facture,
                $verrouille->facture->reference.' : '.number_format((float) $verrouille->montant, 0, ',', ' ').' FCFA ('.$verrouille->moyen.').'
            );
        });
    }

    public function echouer(Paiement $paiement): void
    {
        $modifies = Paiement::where('id', $paiement->id)
            ->where('statut', 'EN_ATTENTE')
            ->update(['statut' => 'ECHEC']);

        if ($modifies > 0) {
            ActivityLog::record(null, 'paiement en ligne échoué', $paiement->facture, $paiement->facture->reference.' : '.number_format((float) $paiement->montant, 0, ',', ' ').' FCFA.');
        }
    }

    /**
     * A payment received outside the platform (cash, transfer, mobile
     * money received directly) recorded by DAG staff.
     */
    public function enregistrerManuel(Facture $facture, float $montant, string $moyen, ?string $reference, MembreEquipe $par): Paiement
    {
        return DB::transaction(function () use ($facture, $montant, $moyen, $reference, $par): Paiement {
            $paiement = Paiement::create([
                'facture_id' => $facture->id,
                'montant' => $montant,
                'moyen' => $moyen,
                'reference_transaction' => $reference,
                'statut' => 'CONFIRME',
                'valide_par_id' => $par->id,
                'date_paiement' => now(),
            ]);

            $this->appliquerAFacture($facture, $montant, $moyen);
            $this->notifierPaiement($facture, $montant);

            ActivityLog::record($par, 'a enregistré un paiement', $facture, $facture->reference.' : '.number_format($montant, 0, ',', ' ').' FCFA ('.$moyen.').');

            return $paiement;
        });
    }

    /**
     * Tells the payer their payment was received, and the team that money came in.
     */
    private function notifierPaiement(Facture $facture, float $montant): void
    {
        $facture->refresh();

        Notifier::envoyer(Notifier::utilisateurDe($facture), new PaiementConfirmeNotification($facture, $montant));

        Notifier::alerterEquipe(
            Donnee::FacturesPaiements,
            Action::Voir,
            'paiement.recu',
            "Paiement reçu — {$facture->reference}",
            number_format($montant, 0, ',', ' ')." FCFA reçus pour {$facture->libelleObjet()}.",
            route('espace-admin.factures.show', $facture),
        );
    }

    private function appliquerAFacture(Facture $facture, float $montant, string $moyen): void
    {
        $montantPaye = (float) $facture->montant_paye + $montant;
        $soldee = $montantPaye >= (float) $facture->montant;

        $facture->update([
            'montant_paye' => $montantPaye,
            'statut' => $soldee ? 'PAYEE' : 'PARTIELLEMENT_PAYEE',
            'moyen_paiement' => $moyen,
        ]);

        if ($soldee && $facture->demande_id !== null) {
            $demande = $facture->demande;
            $etapeActuelle = array_search($demande->statut, Demande::SUIVI_STEPS, true);
            $etapePayee = array_search('PAYEE', Demande::SUIVI_STEPS, true);

            if ($etapeActuelle !== false && $etapeActuelle < $etapePayee) {
                $demande->update(['statut' => 'PAYEE']);
            }

            // Every paid service needs a contract: draft it right away so none is
            // forgotten. DAG reviews it, then sends it to the client for signature.
            if (! $demande->contrat()->exists()) {
                $contrat = ContratRedaction::brouillonPour($demande);

                ActivityLog::record(null, 'contrat généré automatiquement', $contrat, "{$contrat->reference} pour la demande {$demande->reference}.");
            }
        }
    }
}
