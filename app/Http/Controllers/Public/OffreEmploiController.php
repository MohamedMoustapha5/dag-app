<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\OffreEmploi;
use Inertia\Inertia;
use Inertia\Response;

class OffreEmploiController extends Controller
{
    public function __invoke(): Response
    {
        $offres = OffreEmploi::publiees()
            ->with('entreprise')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (OffreEmploi $offre) => [
                'id' => $offre->id,
                'titre' => $offre->titre,
                'description' => $offre->description,
                'entreprise' => $offre->entreprise->nom,
                'typeContrat' => $offre->type_contrat,
                'duree' => $offre->duree,
                'ville' => $offre->ville,
                'dateLimite' => $offre->date_limite?->toDateString(),
            ]);

        return Inertia::render('public/offres-emploi', [
            'offres' => $offres,
        ]);
    }
}
