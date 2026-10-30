<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use App\Models\Facture;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ClientAbonnementController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $client = $request->user()->client;

        $abonnement = $client->abonnements()
            ->with('formule')
            ->where('statut', 'ACTIF')
            ->latest('date_souscription')
            ->first();

        return Inertia::render('espaces/client/abonnements-index', [
            'client' => ['nom' => $client->nom, 'typeLabel' => $client->typeLabel()],
            'abonnement' => $abonnement && $abonnement->formule ? [
                'formuleNom' => $abonnement->formule->nom,
                'servicesInclus' => $abonnement->formule->services_inclus ?? [],
                'montant' => (string) $abonnement->montant_negocie,
                'dateSouscription' => $this->formatDate($abonnement->date_souscription),
                'cycleDebut' => $this->formatDate($abonnement->cycle_debut),
                'echeance' => $this->formatDate($abonnement->echeance),
                'progression' => $abonnement->progressionCycle(),
            ] : null,
            'historiqueFactures' => $client->factures()
                ->where('type', 'ABONNEMENT')
                ->latest('echeance')
                ->take(6)
                ->get()
                ->map(fn (Facture $facture) => [
                    'id' => $facture->id,
                    'echeance' => $this->formatDate($facture->echeance),
                    'moyenPaiement' => $facture->moyen_paiement,
                    'statut' => $facture->statut,
                ]),
        ]);
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
