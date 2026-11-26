<?php

namespace App\Support\Partenariats;

use App\Enums\Action;
use App\Enums\Donnee;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Contrat;
use App\Models\MembreEquipe;
use App\Models\Partenariat;
use App\Notifications\PartenariatContratNotification;
use App\Notifications\PartenariatRefuseNotification;
use App\Notifications\PartenariatValideNotification;
use App\Support\Notifications\Notifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The life of a partnership. The client asks for it, or DAG proposes it;
 * either way DAG sets its duration and the partnership contract is drafted
 * and sent to the client at once. The client signs it, DAG validates it,
 * and the client is an official partner for the agreed duration.
 */
class GestionPartenariats
{
    /**
     * @param  array<array-key, string>  $domaines
     *
     * @throws ValidationException
     */
    public function demander(Client $client, array $domaines, string $message): Partenariat
    {
        $this->verifierEligible($client, 'domaines');

        $partenariat = $client->partenariats()->create([
            'origine' => 'DEMANDE',
            'statut' => 'EN_ETUDE',
            'domaines' => array_values($domaines),
            'message' => $message,
        ]);

        ActivityLog::record(null, 'demande de partenariat reçue', $partenariat, "{$client->nom} : {$partenariat->reference}.");

        Notifier::alerterEquipe(
            Donnee::Partenariats,
            Action::Valider,
            'partenariat.demande',
            "Demande de partenariat — {$client->nom}",
            "{$client->nom} souhaite devenir partenaire de DAG ({$partenariat->libelleDomaines()}). Étudiez sa demande : l'accepter envoie le contrat de partenariat à signer.",
            route('espace-admin.partenariats.index'),
        );

        return $partenariat;
    }

    /**
     * DAG accepts a request: the contract is drafted for the chosen
     * duration and sent to the client for signature.
     */
    public function accepter(Partenariat $partenariat, int $dureeMois, MembreEquipe $par): Partenariat
    {
        abort_unless($partenariat->statut === 'EN_ETUDE', 409, 'Cette demande de partenariat a déjà été traitée.');

        return DB::transaction(function () use ($partenariat, $dureeMois, $par): Partenariat {
            $partenariat->update([
                'duree_mois' => $dureeMois,
                'traite_par_id' => $par->id,
                'traite_le' => now(),
            ]);

            $this->envoyerContrat($partenariat);

            ActivityLog::record($par, 'a accepté la demande de partenariat', $partenariat, "{$partenariat->client->nom} : {$partenariat->libelleDuree()}, contrat {$partenariat->contrat->reference} envoyé.");

            return $partenariat;
        });
    }

    public function refuser(Partenariat $partenariat, string $motif, MembreEquipe $par): void
    {
        abort_unless($partenariat->statut === 'EN_ETUDE', 409, 'Cette demande de partenariat a déjà été traitée.');

        $partenariat->update([
            'statut' => 'REFUSE',
            'motif_refus' => $motif,
            'traite_par_id' => $par->id,
            'traite_le' => now(),
        ]);

        ActivityLog::record($par, 'a refusé la demande de partenariat', $partenariat, "{$partenariat->client->nom} : {$motif}");

        Notifier::envoyer($partenariat->client->user, new PartenariatRefuseNotification($partenariat));
    }

    /**
     * DAG proposes a partnership to a structure that did not ask for it:
     * the contract goes straight to the client for signature.
     *
     * @param  array<array-key, string>  $domaines
     *
     * @throws ValidationException
     */
    public function proposer(Client $client, int $dureeMois, array $domaines, ?string $message, MembreEquipe $par): Partenariat
    {
        $this->verifierEligible($client, 'client_id');

        return DB::transaction(function () use ($client, $dureeMois, $domaines, $message, $par): Partenariat {
            $partenariat = $client->partenariats()->create([
                'origine' => 'PROPOSITION',
                'statut' => 'EN_ETUDE',
                'domaines' => array_values($domaines),
                'message' => $message,
                'duree_mois' => $dureeMois,
                'traite_par_id' => $par->id,
                'traite_le' => now(),
            ]);

            $this->envoyerContrat($partenariat);

            ActivityLog::record($par, 'a proposé un partenariat', $partenariat, "{$client->nom} : {$partenariat->libelleDuree()}, contrat {$partenariat->contrat->reference} envoyé.");

            return $partenariat;
        });
    }

    /**
     * Follows a status change of a partnership contract: its validation
     * opens the partnership, its refusal or termination closes it.
     */
    public function contratMisAJour(Contrat $contrat): void
    {
        $partenariat = Partenariat::where('contrat_id', $contrat->id)->first();

        if ($partenariat === null) {
            return;
        }

        if ($contrat->statut === 'ACTIF' && $partenariat->statut === 'CONTRAT') {
            $partenariat->update([
                'statut' => 'ACTIF',
                'debut' => today()->toDateString(),
                'fin' => $partenariat->estDureeIndeterminee()
                    ? null
                    : today()->addMonthsNoOverflow($partenariat->duree_mois ?? 12)->subDay()->toDateString(),
            ]);

            Notifier::envoyer($partenariat->client->user, new PartenariatValideNotification($partenariat));

            return;
        }

        // Refused, or terminated before DAG validated it: the partnership never started.
        if (in_array($contrat->statut, ['REFUSE', 'RESILIE', 'TERMINE'], true) && $partenariat->statut === 'CONTRAT') {
            $partenariat->update(['statut' => 'ANNULE']);

            return;
        }

        // A refused contract taken up again by DAG brings its partnership back,
        // unless the structure has opened another one meanwhile.
        if (in_array($contrat->statut, ['BROUILLON', 'ENVOYE'], true) && $partenariat->statut === 'ANNULE'
            && ! $partenariat->client->partenariats()->ouverts()->exists()) {
            $partenariat->update(['statut' => 'CONTRAT']);

            return;
        }

        if (in_array($contrat->statut, ['RESILIE', 'TERMINE'], true) && $partenariat->statut === 'ACTIF') {
            $partenariat->update([
                'statut' => 'TERMINE',
                'fin' => $partenariat->fin !== null && $partenariat->fin->lt(today()) ? $partenariat->fin : today()->toDateString(),
            ]);
        }
    }

    /**
     * The partnership of a contract, if the contract seals one.
     */
    public function pourContrat(Contrat $contrat): ?Partenariat
    {
        return $contrat->type === 'CONTRAT_PARTENAIRE'
            ? Partenariat::where('contrat_id', $contrat->id)->first()
            : null;
    }

    /**
     * @throws ValidationException
     */
    private function verifierEligible(Client $client, string $champ): void
    {
        if (! $client->estStructure()) {
            throw ValidationException::withMessages([$champ => 'Le partenariat est réservé aux entreprises et organisations.']);
        }

        if ($client->partenariats()->ouverts()->exists()) {
            throw ValidationException::withMessages([$champ => 'Un partenariat est déjà en cours avec cette structure.']);
        }
    }

    private function envoyerContrat(Partenariat $partenariat): void
    {
        $contrat = Contrat::create([
            'type' => 'CONTRAT_PARTENAIRE',
            'modele_contrat_id' => ContratPartenariat::modele()?->id,
            'client_id' => $partenariat->client_id,
            'contenu' => ContratPartenariat::rediger($partenariat),
            'statut' => 'ENVOYE',
        ]);

        $partenariat->update(['statut' => 'CONTRAT', 'contrat_id' => $contrat->id]);
        $partenariat->setRelation('contrat', $contrat);

        Notifier::envoyer($partenariat->client->user, new PartenariatContratNotification($partenariat));
    }
}
