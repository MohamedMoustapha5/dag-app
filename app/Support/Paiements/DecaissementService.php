<?php

namespace App\Support\Paiements;

use App\Enums\Action;
use App\Enums\Donnee;
use App\Enums\IssueTransaction;
use App\Models\ActivityLog;
use App\Models\Decaissement;
use App\Models\MembreEquipe;
use App\Support\Notifications\Notifier;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Retraits: money sent out of DAG's provider balance to a mobile money
 * account. Every outcome, whether answered at once, notified by webhook
 * or asked for by staff, goes through appliquerResultat().
 */
class DecaissementService
{
    public function __construct(private PaiementService $paiements) {}

    /**
     * @throws PasserelleIndisponible when the active provider cannot send money out.
     */
    public function passerelle(): PasserelleDecaissement
    {
        $passerelle = $this->paiements->passerelle();

        if (! $passerelle instanceof PasserelleDecaissement) {
            throw new PasserelleIndisponible('Le prestataire de paiement actif ne permet pas les retraits.');
        }

        return $passerelle;
    }

    public function disponible(): bool
    {
        return $this->paiements->passerelle() instanceof PasserelleDecaissement;
    }

    /**
     * Records the retrait, then asks the provider to send the money. A
     * provider that refuses it leaves it failed, never pending.
     *
     * @param  string  $telephone  9-digit Cameroonian mobile number.
     *
     * @throws PasserelleIndisponible
     */
    public function demander(float $montant, string $moyen, string $telephone, string $beneficiaire, string $motif, MembreEquipe $par): Decaissement
    {
        $passerelle = $this->passerelle();

        $decaissement = Decaissement::create([
            'montant' => $montant,
            'moyen' => $moyen,
            'telephone' => $telephone,
            'beneficiaire' => $beneficiaire,
            'motif' => $motif,
            'statut' => 'EN_ATTENTE',
            'demande_par_id' => $par->id,
        ]);

        try {
            $resultat = $passerelle->decaisser($decaissement, $par);
        } catch (Throwable $erreur) {
            $decaissement->update(['statut' => 'ECHEC', 'motif_echec' => $erreur instanceof PasserelleIndisponible ? $erreur->getMessage() : 'Erreur du prestataire.']);

            throw $erreur;
        }

        $decaissement->update(['reference_transaction' => $resultat->reference]);

        ActivityLog::record($par, 'a demandé un retrait', $decaissement, $this->resume($decaissement));

        $this->appliquerResultat($decaissement, $resultat);

        return $decaissement->refresh();
    }

    /**
     * Asks the provider again about a retrait still pending.
     *
     * @throws PasserelleIndisponible
     */
    public function actualiser(Decaissement $decaissement): void
    {
        if ($decaissement->statut !== 'EN_ATTENTE' || $decaissement->reference_transaction === null) {
            return;
        }

        $this->appliquerResultat($decaissement, $this->passerelle()->verifierDecaissement($decaissement->reference_transaction));
    }

    /**
     * Idempotent: only a retrait still pending changes state, so a replayed
     * webhook is harmless. A failure alerts the team, since the money did
     * not leave and the need behind the retrait is still unmet.
     */
    public function appliquerResultat(Decaissement $decaissement, ResultatTransaction $resultat): void
    {
        if ($resultat->issue === IssueTransaction::EnAttente) {
            return;
        }

        DB::transaction(function () use ($decaissement, $resultat): void {
            $verrouille = Decaissement::where('id', $decaissement->id)->lockForUpdate()->firstOrFail();

            if ($verrouille->statut !== 'EN_ATTENTE') {
                return;
            }

            if ($resultat->issue === IssueTransaction::Reussi) {
                $verrouille->update(['statut' => 'CONFIRME', 'date_execution' => now()]);
                ActivityLog::record(null, 'retrait effectué', $verrouille, $this->resume($verrouille));

                return;
            }

            $verrouille->update(['statut' => 'ECHEC', 'motif_echec' => $resultat->motifEchec]);
            ActivityLog::record(null, 'retrait échoué', $verrouille, $this->resume($verrouille).($resultat->motifEchec !== null ? " — {$resultat->motifEchec}" : ''));

            Notifier::alerterEquipe(
                Donnee::FacturesPaiements,
                Action::Valider,
                'retrait.echoue',
                'Retrait échoué',
                'Le retrait de '.$this->montant($verrouille).' FCFA vers '.$verrouille->telephone.' a échoué'.($resultat->motifEchec !== null ? " : {$resultat->motifEchec}" : '.'),
                route('espace-admin.retraits.index'),
            );
        });
    }

    private function resume(Decaissement $decaissement): string
    {
        $moyen = config("paiements.moyens_decaissement.{$decaissement->moyen}", $decaissement->moyen);

        return "{$this->montant($decaissement)} FCFA vers {$decaissement->beneficiaire} ({$moyen} {$decaissement->telephone}).";
    }

    private function montant(Decaissement $decaissement): string
    {
        return number_format((float) $decaissement->montant, 0, ',', ' ');
    }
}
