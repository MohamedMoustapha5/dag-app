<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use App\Models\Candidature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientCandidatureController extends Controller
{
    public function index(Request $request): Response
    {
        $entreprise = $request->user('web')->client->entreprisePartenaire;

        $candidatures = Candidature::query()
            ->whereHas('offre', fn ($query) => $query->where('entreprise_id', $entreprise->id))
            ->with(['apprenant.user', 'apprenant.inscriptions.formation', 'offre'])
            ->latest()
            ->get();

        return Inertia::render('espaces/client/candidatures-index', [
            'client' => $request->user('web')->client->espaceHeaderProps(),
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

    /**
     * Le dossier complet du candidat (profil + parcours de formation),
     * consulté par l'entreprise avant de sélectionner ou refuser.
     */
    public function show(Request $request, Candidature $candidature): Response
    {
        Gate::authorize('view', $candidature);

        $candidature->load(['apprenant.user', 'apprenant.inscriptions.formation', 'offre']);
        $apprenant = $candidature->apprenant;

        return Inertia::render('espaces/client/candidatures-show', [
            'client' => $request->user('web')->client->espaceHeaderProps(),
            'candidature' => [
                'id' => $candidature->id,
                'statut' => $candidature->statut,
                'offreId' => $candidature->offre_id,
                'offreTitre' => $candidature->offre->titre,
            ],
            'apprenant' => [
                'nom' => $apprenant->user->name,
                'email' => $apprenant->user->email,
                'telephone' => $apprenant->telephone,
                'ville' => $apprenant->ville,
            ],
            'formations' => $apprenant->inscriptions->map(fn ($inscription) => [
                'nom' => $inscription->formation->nom,
                'statut' => $inscription->statut,
            ]),
            'cv' => [
                'disponible' => $apprenant->cv_path !== null && $apprenant->consentement_cv,
                'nomFichier' => $apprenant->cv_path !== null ? basename($apprenant->cv_path) : null,
            ],
        ]);
    }

    /**
     * Téléchargement du CV du candidat — verrouillé par la même
     * autorisation que le dossier, et par le consentement de partage de
     * l'apprenant (Apprenant::consentement_cv).
     */
    public function cv(Request $request, Candidature $candidature): StreamedResponse
    {
        Gate::authorize('view', $candidature);

        $apprenant = $candidature->apprenant;

        abort_unless($apprenant->cv_path !== null && $apprenant->consentement_cv, 404);

        return Storage::disk('local')->download($apprenant->cv_path, 'CV-'.$apprenant->user->name.'.pdf');
    }
}
