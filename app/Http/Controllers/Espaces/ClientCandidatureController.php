<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use App\Models\Candidature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ClientCandidatureController extends Controller
{
    public function index(Request $request): Response
    {
        $entreprise = $request->user()->client->entreprisePartenaire;

        $candidatures = Candidature::query()
            ->whereHas('offre', fn ($query) => $query->where('entreprise_id', $entreprise->id))
            ->with(['apprenant.user', 'apprenant.inscriptions.formation', 'offre'])
            ->latest()
            ->get();

        return Inertia::render('espaces/client/candidatures-index', [
            'client' => $request->user()->client->espaceHeaderProps(),
            'candidatures' => $candidatures->map(fn (Candidature $candidature) => [
                'id' => $candidature->id,
                'apprenantNom' => $candidature->apprenant->user->name,
                'formationSuivie' => $candidature->formationSuivie(),
                'offreTitre' => $candidature->offre->titre,
                'statut' => $candidature->statut,
            ]),
            'repartition' => [
                'enEtude' => $candidatures->where('statut', 'EN_ETUDE')->count(),
                'retenues' => $candidatures->whereIn('statut', ['RETENUE', 'PLACEE'])->count(),
                'refusees' => $candidatures->where('statut', 'REFUSEE')->count(),
            ],
        ]);
    }

    public function update(Request $request, Candidature $candidature): RedirectResponse
    {
        Gate::authorize('update', $candidature);

        $data = $request->validate([
            'statut' => ['required', Rule::in(['RETENUE', 'REFUSEE'])],
        ]);

        $candidature->update($data);

        return back();
    }
}
