<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use App\Models\Abonnement;
use App\Models\Candidature;
use App\Models\Client;
use App\Models\Contrat;
use App\Models\Demande;
use App\Models\Facture;
use App\Models\OffreEmploi;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ClientDashboardController extends Controller
{
    /** @var array<string, string> */
    private const DEMANDE_EVENT_LABELS = [
        'EN_ETUDE' => 'Demande reçue',
        'DEVIS_ENVOYE' => 'Devis envoyé',
        'PAYEE' => 'Devis payé',
        'EN_COURS' => 'Demande en cours de traitement',
        'LIVREE' => 'Livraison effectuée',
        'TERMINEE' => 'Demande terminée',
        'REFUSEE' => 'Demande refusée',
    ];

    public function __invoke(Request $request): Response
    {
        $client = $request->user()->client;

        if ($client->estPartenaireValide()) {
            return $this->entrepriseDashboard($client);
        }

        return Inertia::render('espaces/client-dashboard', [
            'client' => $client->espaceHeaderProps(),
            'stats' => $this->stats($client),
            'activiteRecente' => $this->activiteRecente($client),
            'formule' => $this->formuleActive($client),
        ]);
    }

    private function entrepriseDashboard(Client $client): Response
    {
        $entreprise = $client->entreprisePartenaire;

        $candidatures = Candidature::query()
            ->whereHas('offre', fn ($query) => $query->where('entreprise_id', $entreprise->id))
            ->get();

        $offresActives = $entreprise->offresEmploi()->where('statut', 'PUBLIEE')->count();
        $placements = $candidatures->where('statut', 'PLACEE')->count();
        $candidaturesCount = $candidatures->count();

        return Inertia::render('espaces/client/entreprise-dashboard', [
            'client' => $client->espaceHeaderProps(),
            'stats' => [
                'offresActives' => $offresActives,
                'candidaturesRecues' => $candidaturesCount,
                'placementsRealises' => $placements,
                'tauxConversion' => $candidaturesCount > 0
                    ? (int) round($placements / $candidaturesCount * 100)
                    : 0,
            ],
            'repartition' => [
                'enEtude' => $candidatures->where('statut', 'EN_ETUDE')->count(),
                'retenues' => $candidatures->whereIn('statut', ['RETENUE', 'PLACEE'])->count(),
                'refusees' => $candidatures->where('statut', 'REFUSEE')->count(),
            ],
            'offres' => $entreprise->offresEmploi()
                ->withCount('candidatures')
                ->latest()
                ->take(5)
                ->get()
                ->map(fn (OffreEmploi $offre) => [
                    'id' => $offre->id,
                    'titre' => $offre->titre,
                    'statut' => $offre->statut,
                    'dateLimite' => $offre->date_limite?->toDateString(),
                    'candidaturesCount' => $offre->candidatures_count,
                ]),
            'candidaturesASigner' => $client->enregistrementsClientOuEntreprise(Contrat::class)
                ->where('statut', 'ENVOYE')->count(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function stats(Client $client): array
    {
        $facturesEnAttente = $client->enregistrementsClientOuEntreprise(Facture::class)
            ->whereIn('statut', ['EN_ATTENTE', 'PARTIELLEMENT_PAYEE'])
            ->get();

        $prochaineEcheance = collect([
            $facturesEnAttente->pluck('echeance')->filter()->min(),
            $client->enregistrementsClientOuEntreprise(Abonnement::class)
                ->where('statut', 'ACTIF')->pluck('echeance')->filter()->min(),
        ])->filter()->min();

        return [
            'demandesEnCours' => $client->demandes()->whereNotIn('statut', ['TERMINEE', 'REFUSEE'])->count(),
            'facturesEnAttenteNombre' => $facturesEnAttente->count(),
            'facturesEnAttenteMontant' => (string) $facturesEnAttente->sum(
                fn (Facture $facture) => $facture->montant - $facture->montant_paye,
            ),
            'prochaineEcheance' => $this->formatDate($prochaineEcheance),
            'contratsASigner' => $client->enregistrementsClientOuEntreprise(Contrat::class)
                ->where('statut', 'ENVOYE')->count(),
        ];
    }

    /**
     * Synthesized from the current state of demandes and paid factures,
     * since no dedicated event log exists yet for the client-facing feed
     * (distinct from the admin-facing activity_logs table).
     *
     * @return array<int, array{label: string, date: string, type: string}>
     */
    private function activiteRecente(Client $client): array
    {
        $demandeEvents = $client->demandes()
            ->with(['service', 'formule'])
            ->where('statut', '!=', 'BROUILLON')
            ->get()
            ->map(fn (Demande $demande) => [
                'label' => (self::DEMANDE_EVENT_LABELS[$demande->statut] ?? $demande->statut).' — '.$demande->libelleObjet(),
                'date' => $demande->updated_at,
                'type' => $this->typeEvenementDemande($demande->statut),
            ]);

        $factureEvents = $client->enregistrementsClientOuEntreprise(Facture::class)
            ->with(['demande.service', 'demande.formule', 'abonnement.formule'])
            ->where('statut', 'PAYEE')
            ->get()
            ->map(fn (Facture $facture) => [
                'label' => 'Paiement confirmé — '.$facture->libelleObjet(),
                'date' => $facture->updated_at,
                'type' => 'succes',
            ]);

        return $demandeEvents->concat($factureEvents)
            ->sortByDesc('date')
            ->take(5)
            ->map(fn (array $event) => [...$event, 'date' => $event['date']->toIso8601String()])
            ->values()
            ->all();
    }

    private function typeEvenementDemande(string $statut): string
    {
        return match ($statut) {
            'PAYEE', 'LIVREE', 'TERMINEE' => 'succes',
            'EN_COURS' => 'attente',
            'REFUSEE' => 'erreur',
            default => 'info',
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function formuleActive(Client $client): ?array
    {
        $abonnement = $client->enregistrementsClientOuEntreprise(Abonnement::class)
            ->with('formule')
            ->where('statut', 'ACTIF')
            ->latest('date_souscription')
            ->first();

        if (! $abonnement || ! $abonnement->formule) {
            return null;
        }

        return [
            'nom' => $abonnement->formule->nom,
            'montant' => (string) $abonnement->montant_negocie,
            'echeance' => $this->formatDate($abonnement->echeance),
            'progression' => $abonnement->progressionCycle(),
            'servicesInclus' => $abonnement->formule->services_inclus ?? [],
        ];
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
