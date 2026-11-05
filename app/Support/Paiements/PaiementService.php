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
use Throwable;

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
     * Starts an online payment of the whole remaining balance. The facture is
     * locked while checking it so two concurrent clicks cannot open two
     * payments for the same balance, and an older attempt left pending past
     * the waiting delay is closed as failed: it can no longer be confirmed
     * and credited on top of the new one.
     *
     * @throws ValidationException
     */
    public function initier(Facture $facture, string $moyen, ?string $telephone): Paiement
    {
        $paiement = DB::transaction(function () use ($facture, $moyen): Paiement {
            $verrouillee = Facture::where('id', $facture->id)->lockForUpdate()->firstOrFail();

            if (! in_array($verrouillee->statut, ['EN_ATTENTE', 'PARTIELLEMENT_PAYEE'], true) || $this->soldeRestant($verrouillee) <= 0) {
                throw ValidationException::withMessages(['moyen' => 'Cette facture est déjà réglée.']);
            }

            if ($this->paiementEnAttente($verrouillee)) {
                throw ValidationException::withMessages([
                    'moyen' => 'Un paiement est déjà en attente de confirmation pour cette facture.',
                ]);
            }

            $verrouillee->paiements()->where('statut', 'EN_ATTENTE')->update(['statut' => 'ECHEC']);

            return Paiement::create([
                'facture_id' => $verrouillee->id,
                'montant' => $this->soldeRestant($verrouillee),
                'moyen' => $moyen,
                'statut' => 'EN_ATTENTE',
            ]);
        });

        // A provider that refuses the payment must not leave it pending, or it
        // would block any new attempt until the waiting delay runs out.
        try {
            $reference = $this->passerelle()->initier($paiement, $telephone);
        } catch (Throwable $erreur) {
            $paiement->update(['statut' => 'ECHEC']);

            throw $erreur;
        }

        $paiement->update(['reference_transaction' => $reference]);

        return $paiement;
    }

    /**
     * Idempotent: only a paiement still EN_ATTENTE can be confirmed. The
     * facture is locked too, so two payments settled at the same moment
     * both count instead of one overwriting the other's amount.
     *
     * A provider confirming a payment already closed as failed (it expired
     * while a newer attempt was opened) is not credited: the team is alerted
     * to reconcile or refund it by hand.
     */
    public function confirmer(Paiement $paiement, ?MembreEquipe $par = null): void
    {
        DB::transaction(function () use ($paiement, $par): void {
            $verrouille = Paiement::where('id', $paiement->id)->lockForUpdate()->firstOrFail();

            if ($verrouille->statut === 'ECHEC' && $par === null) {
                $this->signalerPaiementTardif($verrouille);

                return;
            }

            if ($verrouille->statut !== 'EN_ATTENTE') {
                return;
            }

            $facture = Facture::where('id', $verrouille->facture_id)->lockForUpdate()->firstOrFail();

            $verrouille->update([
                'statut' => 'CONFIRME',
                'date_paiement' => now(),
                'valide_par_id' => $par?->id,
            ]);

            $this->appliquerAFacture($facture, (float) $verrouille->montant, $verrouille->moyen);
            $this->notifierPaiement($facture, (float) $verrouille->montant);

            ActivityLog::record(
                $par,
                $par === null ? 'paiement en ligne confirmé' : 'a confirmé un paiement',
                $facture,
                $facture->reference.' : '.number_format((float) $verrouille->montant, 0, ',', ' ').' FCFA ('.$verrouille->moyen.').'
            );
        });
    }

    private function signalerPaiementTardif(Paiement $paiement): void
    {
        $facture = $paiement->facture;
        $montant = number_format((float) $paiement->montant, 0, ',', ' ');

        ActivityLog::record(null, 'paiement en ligne reçu après expiration', $facture, "{$facture->reference} : {$montant} FCFA ({$paiement->reference_transaction}), non crédité.");

        Notifier::alerterEquipe(
            Donnee::FacturesPaiements,
            Action::Modifier,
            'paiement.tardif',
            "Paiement à rapprocher — {$facture->reference}",
            "Le prestataire confirme {$montant} FCFA ({$paiement->reference_transaction}) sur une tentative déjà expirée : vérifiez et remboursez ou enregistrez-le à la main.",
            route('espace-admin.factures.show', $facture),
        );
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
     * money received directly) recorded by DAG staff. It can never exceed
     * what is still owed on the facture.
     *
     * @throws ValidationException
     */
    public function enregistrerManuel(Facture $facture, float $montant, string $moyen, ?string $reference, MembreEquipe $par): Paiement
    {
        return DB::transaction(function () use ($facture, $montant, $moyen, $reference, $par): Paiement {
            $facture = Facture::where('id', $facture->id)->lockForUpdate()->firstOrFail();
            $solde = $this->soldeRestant($facture);

            if ($solde <= 0) {
                throw ValidationException::withMessages(['montant' => 'Cette facture est déjà réglée.']);
            }

            if ($montant > $solde) {
                throw ValidationException::withMessages([
                    'montant' => 'Le montant dépasse le solde restant ('.number_format($solde, 0, ',', ' ').' FCFA).',
                ]);
            }

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
