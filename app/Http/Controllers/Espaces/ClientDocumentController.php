<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use App\Models\Contrat;
use App\Models\Facture;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientDocumentController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $client = $request->user()->client;

        $contrats = $client->enregistrementsClientOuEntreprise(Contrat::class)
            ->with(['demande.service', 'demande.formule', 'abonnement.formule', 'candidature.offre'])
            ->latest()
            ->get();

        $factures = $client->enregistrementsClientOuEntreprise(Facture::class)
            ->with(['demande.service', 'demande.formule', 'abonnement.formule'])
            ->latest()
            ->get();

        return Inertia::render('espaces/client/documents', [
            'client' => $client->espaceHeaderProps(),
            'contrats' => $contrats->map(fn (Contrat $contrat) => [
                'id' => $contrat->id,
                'reference' => $contrat->reference,
                'objet' => $contrat->libelleObjet(),
                'statut' => $contrat->statut,
                'fichierPdf' => $contrat->fichier_pdf,
            ]),
            'factures' => $factures->map(fn (Facture $facture) => [
                'id' => $facture->id,
                'reference' => $facture->reference,
                'montant' => (string) $facture->montant,
                'montantPaye' => (string) $facture->montant_paye,
                'statut' => $facture->statut,
            ]),
        ]);
    }
}
