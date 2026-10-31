<?php

namespace App\Http\Controllers\Espaces;

use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\LigneTarifaire;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminGrilleTarifaireController extends Controller
{
    public function index(Request $request): Response
    {
        $membre = $request->user()->membreEquipe;

        $lignes = LigneTarifaire::query()
            ->orderBy('categorie_ordre')
            ->orderBy('ordre')
            ->get();

        return Inertia::render('espaces/admin-grille-tarifaire', [
            'lignes' => $lignes->map(fn (LigneTarifaire $ligne) => [
                'id' => $ligne->id,
                'categorie' => $ligne->categorie,
                'categorieOrdre' => $ligne->categorie_ordre,
                'prestation' => $ligne->prestation,
                'tarif' => $ligne->tarif,
                'ordre' => $ligne->ordre,
            ]),
            'categoriesExistantes' => $lignes
                ->unique('categorie')
                ->sortBy('categorie_ordre')
                ->map(fn (LigneTarifaire $ligne) => [
                    'nom' => $ligne->categorie,
                    'ordre' => $ligne->categorie_ordre,
                ])
                ->values(),
            'droits' => [
                'creer' => $membre->peut(Donnee::Catalogue, Action::Creer),
                'modifier' => $membre->peut(Donnee::Catalogue, Action::Modifier),
                'supprimer' => $membre->peut(Donnee::Catalogue, Action::Supprimer),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->membreEquipe->peut(Donnee::Catalogue, Action::Creer), 403);

        $data = $request->validate([
            'categorie' => ['required', 'string', 'max:191'],
            'categorie_ordre' => ['required', 'integer', 'min:1'],
            'prestation' => ['required', 'string', 'max:191'],
            'tarif' => ['required', 'string', 'max:191'],
            'ordre' => ['nullable', 'integer', 'min:0'],
        ]);

        LigneTarifaire::create([
            ...$data,
            'ordre' => $data['ordre'] ?? 0,
        ]);

        return back();
    }

    public function update(Request $request, LigneTarifaire $ligneTarifaire): RedirectResponse
    {
        abort_unless($request->user()->membreEquipe->peut(Donnee::Catalogue, Action::Modifier), 403);

        $data = $request->validate([
            'prestation' => ['required', 'string', 'max:191'],
            'tarif' => ['required', 'string', 'max:191'],
        ]);

        $ligneTarifaire->update($data);

        return back();
    }

    public function destroy(Request $request, LigneTarifaire $ligneTarifaire): RedirectResponse
    {
        abort_unless($request->user()->membreEquipe->peut(Donnee::Catalogue, Action::Supprimer), 403);

        $ligneTarifaire->delete();

        return back();
    }
}
