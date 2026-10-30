<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use App\Models\Contrat;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientContratController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $client = $request->user()->client;

        $contrats = $client->contrats()
            ->with(['demande.service', 'demande.formule', 'abonnement.formule'])
            ->latest()
            ->get();

        return Inertia::render('espaces/client/contrats-index', [
            'client' => ['nom' => $client->nom, 'typeLabel' => $client->typeLabel()],
            'contrats' => $contrats->map(fn (Contrat $contrat) => [
                'id' => $contrat->id,
                'reference' => $contrat->reference,
                'objet' => $contrat->libelleObjet(),
                'statut' => $contrat->statut,
                'fichierPdf' => $contrat->fichier_pdf,
            ]),
        ]);
    }
}
