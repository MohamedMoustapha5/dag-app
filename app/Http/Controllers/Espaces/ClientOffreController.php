<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use App\Models\Candidature;
use App\Models\OffreEmploi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ClientOffreController extends Controller
{
    public function index(Request $request): Response
    {
        $entreprise = $request->user()->client->entreprisePartenaire;

        $offres = $entreprise->offresEmploi()
            ->withCount('candidatures')
            ->with(['candidatures' => fn ($query) => $query->with(['apprenant.user', 'apprenant.inscriptions.formation'])->latest()->take(3)])
            ->latest()
            ->get();

        return Inertia::render('espaces/client/offres-index', [
            'client' => $request->user()->client->espaceHeaderProps(),
            'offres' => $offres->map(fn (OffreEmploi $offre) => [
                'id' => $offre->id,
                'titre' => $offre->titre,
                'statut' => $offre->statut,
                'dateLimite' => $offre->date_limite?->toDateString(),
                'candidaturesCount' => $offre->candidatures_count,
                'candidaturesApercu' => $offre->candidatures->map(fn (Candidature $candidature) => [
                    'id' => $candidature->id,
                    'apprenantNom' => $candidature->apprenant->user->name,
                    'formationSuivie' => $candidature->formationSuivie(),
                    'statut' => $candidature->statut,
                ]),
            ]),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('espaces/client/offres-create', [
            'client' => $request->user()->client->espaceHeaderProps(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'titre' => ['required', 'string', 'max:191'],
            'description' => ['required', 'string', 'max:5000'],
            'type_contrat' => ['required', 'string', 'in:STAGE,CDD,CDI'],
            'duree' => ['nullable', 'string', 'max:191'],
            'competences' => ['nullable', 'string', 'max:2000'],
            'ville' => ['nullable', 'string', 'max:191'],
            'date_limite' => ['nullable', 'date'],
        ]);

        $offre = $request->user()->client->entreprisePartenaire->offresEmploi()->create([
            ...$data,
            'statut' => 'PUBLIEE',
        ]);

        return to_route('espace-client.offres.show', $offre);
    }

    public function show(Request $request, OffreEmploi $offre): Response
    {
        Gate::authorize('view', $offre);

        $offre->load(['candidatures.apprenant.user', 'candidatures.apprenant.inscriptions.formation']);

        return Inertia::render('espaces/client/offres-show', [
            'client' => $request->user()->client->espaceHeaderProps(),
            'offre' => [
                'id' => $offre->id,
                'titre' => $offre->titre,
                'description' => $offre->description,
                'statut' => $offre->statut,
                'typeContrat' => $offre->type_contrat,
                'ville' => $offre->ville,
                'dateLimite' => $offre->date_limite?->toDateString(),
            ],
            'candidatures' => $offre->candidatures->map(fn (Candidature $candidature) => [
                'id' => $candidature->id,
                'apprenantNom' => $candidature->apprenant->user->name,
                'formationSuivie' => $candidature->formationSuivie(),
                'statut' => $candidature->statut,
            ]),
        ]);
    }
}
