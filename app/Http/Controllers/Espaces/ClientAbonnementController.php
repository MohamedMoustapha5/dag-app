<?php

namespace App\Http\Controllers\Espaces;

use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\Abonnement;
use App\Models\Client;
use App\Support\Notifications\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ClientAbonnementController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $client = $request->user('web')->client;

        $abonnements = $client->enregistrementsClientOuEntreprise(Abonnement::class)
            ->with(['formule', 'factures'])
            ->where('statut', '!=', 'BROUILLON')
            ->get()
            ->sortBy(fn (Abonnement $abonnement) => [
                $abonnement->statut === 'ACTIF' ? 0 : 1,
                $this->formatDate($abonnement->echeance) ?? '9999-12-31',
            ])
            ->values();

        $cartes = [];
        foreach ($abonnements as $abonnement) {
            $cartes[] = $this->carte($abonnement);
        }

        $actifs = 0;
        $aRenouveler = 0;
        $mensuel = 0.0;
        foreach ($cartes as $carte) {
            if ($carte['etat'] !== 'termine' && $carte['etat'] !== 'suspendu' && $carte['etat'] !== 'expire') {
                $actifs++;
                $mensuel += (float) $carte['montant'];
            }
            if ($carte['peutRenouveler']) {
                $aRenouveler++;
            }
        }

        return Inertia::render('espaces/client/abonnements-index', [
            'client' => $client->espaceHeaderProps(),
            'resume' => [
                'actifs' => $actifs,
                'aRenouveler' => $aRenouveler,
                'mensuel' => (string) $mensuel,
            ],
            'abonnements' => $cartes,
        ]);
    }

    /**
     * Asks the DAG team to renew a subscription. A renewal is a regular formule
     * request, so it follows the usual path: devis and contract, signature, then payment.
     */
    public function renouveler(Request $request, Abonnement $abonnement): RedirectResponse
    {
        $client = $request->user('web')->client;

        abort_unless($this->appartient($client, $abonnement), 404);
        abort_unless($abonnement->peutEtreRenouvele(), 409, 'Cet abonnement ne peut pas être renouvelé pour le moment.');

        $formule = $abonnement->formule;
        abort_if($formule === null, 409, "La formule de cet abonnement n'existe plus : contactez l'équipe DAG.");

        $enCours = $abonnement->demandeRenouvellementEnCours();

        if ($enCours !== null) {
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Une demande de renouvellement est déjà en cours.']);

            return to_route('espace-client.demandes.show', $enCours);
        }

        $echeance = $abonnement->echeance !== null ? Carbon::parse($abonnement->echeance)->format('d/m/Y') : null;

        $demande = $client->demandes()->create([
            'formule_id' => $formule->id,
            'description' => "Renouvellement de l'abonnement « {$formule->nom} »".($echeance !== null ? " (échéance du {$echeance})" : '').'.',
            'statut' => 'EN_ETUDE',
        ]);

        Notifier::alerterEquipe(
            Donnee::DemandesClients,
            Action::Modifier,
            'demande.nouvelle',
            "Renouvellement d'abonnement — {$demande->reference}",
            "{$client->nom} souhaite renouveler la formule {$formule->nom}.",
            route('espace-admin.demandes.show', $demande),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => "Demande de renouvellement envoyée : l'équipe DAG vous envoie un devis."]);

        return to_route('espace-client.demandes.show', $demande);
    }

    /**
     * @return array<string, mixed>
     */
    private function carte(Abonnement $abonnement): array
    {
        $mois = $abonnement->moisDuCycle();
        $paiementDuMois = null;
        foreach ($mois as $segment) {
            if ($segment['courant']) {
                $paiementDuMois = $segment['facture'];
            }
        }
        $demandeRenouvellement = $abonnement->demandeRenouvellementEnCours();

        $factures = [];
        foreach ($abonnement->factures->sortByDesc('echeance')->take(3) as $facture) {
            $factures[] = [
                'id' => $facture->id,
                'echeance' => $this->formatDate($facture->echeance),
                'statut' => $facture->statut,
            ];
        }

        return [
            'id' => $abonnement->id,
            'formuleNom' => $abonnement->formule !== null ? $abonnement->formule->nom : 'Formule retirée du catalogue',
            'servicesInclus' => $abonnement->formule !== null ? ($abonnement->formule->services_inclus ?? []) : [],
            'montant' => (string) $abonnement->montant_negocie,
            'dateSouscription' => $this->formatDate($abonnement->date_souscription),
            'cycleDebut' => $this->formatDate($abonnement->cycle_debut),
            'echeance' => $this->formatDate($abonnement->echeance),
            'progression' => $abonnement->statut === 'TERMINE' ? 100 : $abonnement->progressionCycle(),
            'joursRestants' => $abonnement->joursRestants(),
            'etat' => $abonnement->etatEcheance(),
            'peutRenouveler' => $abonnement->peutEtreRenouvele(),
            'demandeRenouvellement' => $demandeRenouvellement?->id,
            'mois' => $mois,
            'paiementDuMois' => $paiementDuMois,
            'factures' => $factures,
        ];
    }

    private function appartient(Client $client, Abonnement $abonnement): bool
    {
        return $client->enregistrementsClientOuEntreprise(Abonnement::class)
            ->whereKey($abonnement->getKey())
            ->exists();
    }

    /**
     * Normalizes a `date`-cast attribute to a Y-m-d string. Explicit
     * rather than a nullsafe `?->toDateString()` because Larastan's
     * schema-derived column type for `date` (as opposed to `datetime`)
     * loses the Carbon type, so these are statically `string` here.
     */
    private function formatDate(?string $value): ?string
    {
        return $value === null ? null : Carbon::parse($value)->toDateString();
    }
}
