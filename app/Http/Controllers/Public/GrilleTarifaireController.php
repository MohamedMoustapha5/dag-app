<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\LigneTarifaire;
use Inertia\Inertia;
use Inertia\Response;

class GrilleTarifaireController extends Controller
{
    public function __invoke(): Response
    {
        $categories = LigneTarifaire::query()
            ->orderBy('categorie_ordre')
            ->orderBy('ordre')
            ->get()
            ->groupBy('categorie')
            ->map(fn ($lignes, $categorie) => [
                'categorie' => $categorie,
                'ordre' => $lignes->first()->categorie_ordre,
                'lignes' => $lignes->map(fn (LigneTarifaire $ligne) => [
                    'id' => $ligne->id,
                    'prestation' => $ligne->prestation,
                    'tarif' => $ligne->tarif,
                ])->values(),
            ])
            ->sortBy('ordre')
            ->values();

        return Inertia::render('public/grille-tarifaire', [
            'categories' => $categories,
        ]);
    }
}
