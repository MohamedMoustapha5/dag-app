<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use App\Models\Facture;
use App\Support\FacturePdf;
use App\Support\Paiements\PaiementService;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class ClientFactureController extends Controller
{
    public function __invoke(Request $request, PaiementService $paiements): Response
    {
        $client = $request->user('web')->client;

        $factures = $client->enregistrementsClientOuEntreprise(Facture::class)
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
            'client' => $client->espaceHeaderProps(),
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
                'solde' => (string) $paiements->soldeRestant($facture),
                'paiementEnAttente' => $paiements->paiementEnAttente($facture),
            ]),
            'moyensPaiement' => $paiements->moyens(),
        ]);
    }

    /**
     * Only a paid facture of this client (or of their entreprise) can be
     * downloaded; anything else is a 404 so references can't be probed.
     */
    public function pdf(Request $request, Facture $facture): HttpResponse
    {
        $client = $request->user('web')->client;

        abort_unless(
            $client->enregistrementsClientOuEntreprise(Facture::class)->where('id', $facture->id)->exists(),
            404
        );

        return FacturePdf::telecharger($facture);
    }
}
