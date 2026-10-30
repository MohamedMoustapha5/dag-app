<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use App\Models\Facture;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientFactureController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $client = $request->user()->client;

        $factures = $client->factures()
            ->with(['demande.service', 'demande.formule', 'abonnement.formule'])
            ->latest()
            ->get();

        $regleCetteAnnee = $factures
            ->where('statut', 'PAYEE')
            ->filter(fn (Facture $facture) => $facture->updated_at->year === now()->year)
            ->sum('montant_paye');

        $enAttente = $factures
            ->whereIn('statut', ['EN_ATTENTE', 'PARTIELLEMENT_PAYEE'])
            ->sum(fn (Facture $facture) => $facture->montant - $facture->montant_paye);

        $moyenPlusUtilise = $factures->whereNotNull('moyen_paiement')->countBy('moyen_paiement')->sortDesc()->keys()->first();

        return Inertia::render('espaces/client/factures-index', [
            'client' => ['nom' => $client->nom, 'typeLabel' => $client->typeLabel()],
            'stats' => [
                'regleCetteAnnee' => (string) $regleCetteAnnee,
                'enAttente' => (string) $enAttente,
                'moyenPlusUtilise' => $moyenPlusUtilise,
            ],
            'factures' => $factures->map(fn (Facture $facture) => [
                'id' => $facture->id,
                'reference' => $facture->reference,
                'objet' => $facture->libelleObjet(),
                'montant' => (string) $facture->montant,
                'moyenPaiement' => $facture->moyen_paiement,
                'statut' => $facture->statut,
            ]),
        ]);
    }
}
