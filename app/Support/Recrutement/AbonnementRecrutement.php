<?php

namespace App\Support\Recrutement;

use App\Enums\Action;
use App\Enums\Donnee;
use App\Models\AccesRecrutement;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Facture;
use App\Models\ForfaitRecrutement;
use App\Models\MembreEquipe;
use App\Notifications\AccesRecrutementOuvertNotification;
use App\Support\Notifications\Notifier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Recruitment access by subscription. An entreprise or an organisation
 * chooses a forfait and gets its facture; once paid, the period is opened,
 * after the end of any period already secured (a renewal never loses a
 * paid day). DAG can also offer a period, which follows the same rule.
 */
class AbonnementRecrutement
{
    /**
     * The facture to pay for the chosen forfait. A subscription still
     * awaiting payment is switched to the new forfait rather than doubled,
     * as long as no online payment of it has started.
     *
     * @throws ValidationException
     */
    public function souscrire(Client $client, ForfaitRecrutement $forfait): Facture
    {
        if (! $client->estStructure()) {
            throw ValidationException::withMessages(['forfait_id' => 'L’abonnement recrutement est réservé aux entreprises et organisations.']);
        }

        return DB::transaction(function () use ($client, $forfait): Facture {
            $enAttente = $client->accesRecrutement()
                ->where('statut', 'EN_ATTENTE_PAIEMENT')
                ->with('facture.paiements')
                ->latest()
                ->first();

            if ($enAttente !== null && $enAttente->facture !== null) {
                $facture = $enAttente->facture;

                if ($facture->paiements->contains(fn ($paiement) => $paiement->statut !== 'ECHEC')) {
                    throw ValidationException::withMessages([
                        'forfait_id' => 'Un paiement de votre abonnement est déjà en cours : finalisez-le depuis la facture.',
                    ]);
                }

                $enAttente->update([
                    'forfait_id' => $forfait->id,
                    'libelle' => $forfait->libelle,
                    'duree_mois' => $forfait->duree_mois,
                    'montant' => $forfait->prix,
                ]);
                $facture->update(['montant' => $forfait->prix]);

                return $facture;
            }

            $acces = $client->accesRecrutement()->create([
                'forfait_id' => $forfait->id,
                'libelle' => $forfait->libelle,
                'duree_mois' => $forfait->duree_mois,
                'montant' => $forfait->prix,
                'origine' => 'PAIEMENT',
                'statut' => 'EN_ATTENTE_PAIEMENT',
            ]);

            return Facture::create([
                'type' => 'ABONNEMENT',
                'client_id' => $client->id,
                'acces_recrutement_id' => $acces->id,
                'montant' => $forfait->prix,
                'montant_paye' => 0,
                'statut' => 'EN_ATTENTE',
            ]);
        });
    }

    /**
     * Opens a paid or offered period: it starts the day after the last
     * period already secured, or today. The client's partner record (to
     * which its job offers belong) is created or reactivated.
     */
    public function activer(AccesRecrutement $acces): void
    {
        if ($acces->statut === 'ACTIF') {
            return;
        }

        $client = $acces->client;
        $dejaAcquis = $client->finAccesRecrutement();
        $debut = $dejaAcquis !== null ? $dejaAcquis->copy()->addDay() : Carbon::today();

        $acces->update([
            'statut' => 'ACTIF',
            'debut' => $debut->toDateString(),
            'fin' => $debut->copy()->addMonthsNoOverflow($acces->duree_mois)->subDay()->toDateString(),
        ]);

        $this->assurerPartenaire($client);

        $acces->refresh();

        ActivityLog::record(
            $acces->offertPar,
            $acces->origine === 'OFFERT' ? 'a offert un accès recrutement' : 'abonnement recrutement payé',
            $client,
            "{$client->nom} : {$acces->libelle}, du {$acces->debut->format('d/m/Y')} au {$acces->fin->format('d/m/Y')}.",
        );

        Notifier::envoyer($client->user, new AccesRecrutementOuvertNotification($acces));

        if ($acces->origine === 'PAIEMENT') {
            Notifier::alerterEquipe(
                Donnee::EntreprisesOffres,
                Action::Voir,
                'recrutement.abonnement',
                "Abonnement recrutement — {$client->nom}",
                "{$client->nom} a payé l’abonnement recrutement {$acces->libelle} : accès ouvert jusqu’au {$acces->fin->format('d/m/Y')}.",
                route('espace-admin.recrutement.index'),
            );
        }
    }

    /**
     * DAG offers a period of access, without facture.
     */
    public function offrir(Client $client, int $mois, MembreEquipe $par): AccesRecrutement
    {
        $acces = $client->accesRecrutement()->create([
            'libelle' => $mois === 12 ? '1 an' : "{$mois} mois",
            'duree_mois' => $mois,
            'montant' => 0,
            'origine' => 'OFFERT',
            'statut' => 'EN_ATTENTE_PAIEMENT',
            'offert_par_id' => $par->id,
        ]);

        $this->activer($acces);

        return $acces->refresh();
    }

    /**
     * The partner record the client's job offers belong to, created from
     * the account on its first access, reactivated afterwards.
     */
    private function assurerPartenaire(Client $client): void
    {
        $partenaire = $client->entreprisePartenaire()->firstOrNew();

        $partenaire->fill($partenaire->exists ? ['statut' => 'ACTIF'] : [
            'nom' => $client->nom,
            'secteur' => $client->secteur_activite,
            'email' => $client->user->email,
            'telephone' => $client->telephone,
            'statut' => 'ACTIF',
        ])->save();

        $client->unsetRelation('entreprisePartenaire');
    }
}
