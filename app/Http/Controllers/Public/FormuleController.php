<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Formule;
use Inertia\Inertia;
use Inertia\Response;

class FormuleController extends Controller
{
    public function __invoke(): Response
    {
        $formules = Formule::where('statut', 'PUBLIEE')
            ->orderBy('populaire', 'desc')
            ->get()
            ->map(fn (Formule $formule) => [
                'id' => $formule->id,
                'nom' => $formule->nom,
                'contenu' => $formule->contenu,
                'servicesInclus' => $formule->services_inclus ?? [],
                'prixAffiche' => $formule->prix_affiche,
                'populaire' => $formule->populaire,
            ]);

        return Inertia::render('public/abonnement', [
            'formules' => $formules,
        ]);
    }
}
