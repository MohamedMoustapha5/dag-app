<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use App\Models\Abonnement;
use App\Models\Formule;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The subscription formules, browsed from the client espace (the espace
 * counterpart of the public "Abonnement" page): each one opens a formule
 * request, and the formules the client already subscribes to are flagged.
 */
class ClientFormuleController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $client = $request->user('web')->client;

        $souscrites = $client->enregistrementsClientOuEntreprise(Abonnement::class)
            ->where('statut', 'ACTIF')
            ->pluck('formule_id')
            ->filter()
            ->all();

        return Inertia::render('espaces/client/formules-index', [
            'client' => $client->espaceHeaderProps(),
            'formules' => Formule::query()
                ->where('statut', 'PUBLIEE')
                ->orderByDesc('populaire')
                ->orderBy('nom')
                ->get()
                ->map(fn (Formule $formule) => [
                    'id' => $formule->id,
                    'nom' => $formule->nom,
                    'contenu' => $formule->contenu,
                    'servicesInclus' => $formule->services_inclus ?? [],
                    'prixAffiche' => $formule->prix_affiche,
                    'populaire' => $formule->populaire,
                    'souscrite' => in_array($formule->id, $souscrites, true),
                ]),
        ]);
    }
}
