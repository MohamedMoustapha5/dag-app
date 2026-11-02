<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\Formation;
use App\Models\FormationSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminFormationController extends Controller
{
    use SharesAdminEspaceProps;

    public function index(Request $request): Response
    {
        $membre = $request->user()->membreEquipe;
        abort_unless($membre->peut(Donnee::Formations, Action::Voir), 403);

        $sessions = FormationSession::query()
            ->with('formation')
            ->orderByDesc('date_debut')
            ->get();

        return Inertia::render('espaces/admin-formations', [
            ...$this->adminEspaceProps($membre),
            'peutCreer' => $membre->peut(Donnee::Formations, Action::Creer),
            'peutModifier' => $membre->peut(Donnee::Formations, Action::Modifier),
            'peutSupprimer' => $membre->peut(Donnee::Formations, Action::Supprimer),
            'sessions' => $sessions->map(fn (FormationSession $session) => [
                'id' => $session->id,
                'formationId' => $session->formation_id,
                'formationNom' => $session->formation->nom,
                'ville' => $session->ville,
                'mode' => $session->mode,
                'lieu' => $session->lieu,
                'formateur' => $session->formateur,
                'dateDebut' => $session->date_debut?->toDateString(),
                'dateFin' => $session->date_fin?->toDateString(),
                'places' => $session->places,
                'inscritsCount' => $session->places - $session->placesRestantes(),
                'statut' => $session->statut,
                'image' => $session->image,
            ]),
        ]);
    }

    /**
     * Quick "nouvelle session" entry point used by the Formations & sessions
     * index: the admin only types the training's title, not the full
     * catalog form, so this resolves (or creates, on first use of that
     * title) the underlying Formation and attaches a new session to it in
     * one step.
     */
    public function storeRapide(Request $request): RedirectResponse
    {
        $membre = $request->user()->membreEquipe;
        abort_unless($membre->peut(Donnee::Formations, Action::Creer), 403);

        $data = $request->validate([
            'formation_nom' => ['required', 'string', 'max:191'],
            'ville' => ['required', 'string', 'max:191'],
            'mode' => ['required', Rule::in(['PRESENTIEL', 'EN_LIGNE', 'LES_DEUX'])],
            'lieu' => ['nullable', 'string', 'max:191'],
            'formateur' => ['nullable', 'string', 'max:191'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'places' => ['required', 'integer', 'min:1'],
            'statut' => ['required', Rule::in(['BROUILLON', 'OUVERTE', 'COMPLETE', 'TERMINEE'])],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);

        $formation = Formation::firstOrCreate(
            ['nom' => $data['formation_nom']],
            ['description' => $data['formation_nom'], 'statut' => 'PUBLIEE'],
        );

        $imagePath = $this->storeImage($request);

        if ($imagePath !== null && $formation->image === null) {
            $formation->update(['image' => $imagePath]);
        }

        $formation->sessions()->create([
            'ville' => $data['ville'],
            'mode' => $data['mode'],
            'lieu' => $data['lieu'] ?? null,
            'formateur' => $data['formateur'] ?? null,
            'date_debut' => $data['date_debut'] ?? null,
            'date_fin' => $data['date_fin'] ?? null,
            'places' => $data['places'],
            'statut' => $data['statut'],
            'image' => $imagePath,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Session créée.']);

        return back();
    }

    public function show(Request $request, Formation $formation): Response
    {
        $membre = $request->user()->membreEquipe;
        abort_unless($membre->peut(Donnee::Formations, Action::Voir), 403);

        $formation->load(['sessions' => fn ($query) => $query->withCount('seances')->orderByDesc('date_debut')]);

        return Inertia::render('espaces/admin-formation-show', [
            ...$this->adminEspaceProps($membre),
            'peutModifier' => $membre->peut(Donnee::Formations, Action::Modifier),
            'peutCreer' => $membre->peut(Donnee::Formations, Action::Creer),
            'formation' => [
                'id' => $formation->id,
                'nom' => $formation->nom,
                'description' => $formation->description,
                'categorie' => $formation->categorie,
                'duree' => $formation->duree,
                'prerequis' => $formation->prerequis,
                'statut' => $formation->statut,
            ],
            'sessions' => $formation->sessions->map(fn (FormationSession $session) => [
                'id' => $session->id,
                'ville' => $session->ville,
                'mode' => $session->mode,
                'dateDebut' => $session->date_debut?->toDateString(),
                'dateFin' => $session->date_fin?->toDateString(),
                'places' => $session->places,
                'placesRestantes' => $session->placesRestantes(),
                'statut' => $session->statut,
                'seancesCount' => $session->seances_count,
                'image' => $session->image,
            ]),
        ]);
    }

    public function update(Request $request, Formation $formation): RedirectResponse
    {
        $membre = $request->user()->membreEquipe;
        abort_unless($membre->peut(Donnee::Formations, Action::Modifier), 403);

        $data = $request->validate([
            'nom' => ['required', 'string', 'max:191'],
            'description' => ['required', 'string'],
            'categorie' => ['nullable', 'string', 'max:191'],
            'duree' => ['nullable', 'string', 'max:191'],
            'prerequis' => ['nullable', 'string', 'max:500'],
            'statut' => ['required', Rule::in(['BROUILLON', 'PUBLIEE', 'EN_COURS', 'TERMINEE'])],
        ]);

        $formation->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Formation mise à jour.']);

        return back();
    }

    public function storeSession(Request $request, Formation $formation): RedirectResponse
    {
        $membre = $request->user()->membreEquipe;
        abort_unless($membre->peut(Donnee::Formations, Action::Creer), 403);

        $data = $request->validate([
            'ville' => ['required', 'string', 'max:191'],
            'mode' => ['required', Rule::in(['PRESENTIEL', 'EN_LIGNE', 'LES_DEUX'])],
            'lieu' => ['nullable', 'string', 'max:191'],
            'formateur' => ['nullable', 'string', 'max:191'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'places' => ['required', 'integer', 'min:1'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);

        unset($data['image']);

        $formation->sessions()->create([
            ...$data,
            'statut' => 'BROUILLON',
            'image' => $this->storeImage($request),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Session créée.']);

        return back();
    }

    public function updateSession(Request $request, FormationSession $formationSession): RedirectResponse
    {
        $membre = $request->user()->membreEquipe;
        abort_unless($membre->peut(Donnee::Formations, Action::Modifier), 403);

        $data = $request->validate([
            'ville' => ['required', 'string', 'max:191'],
            'mode' => ['required', Rule::in(['PRESENTIEL', 'EN_LIGNE', 'LES_DEUX'])],
            'lieu' => ['nullable', 'string', 'max:191'],
            'formateur' => ['nullable', 'string', 'max:191'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'places' => ['required', 'integer', 'min:1'],
            'statut' => ['required', Rule::in(['BROUILLON', 'OUVERTE', 'COMPLETE', 'TERMINEE'])],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);

        unset($data['image']);

        $imagePath = $this->storeImage($request);

        if ($imagePath !== null) {
            $data['image'] = $imagePath;
        }

        $formationSession->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Session mise à jour.']);

        return back();
    }

    public function destroySession(Request $request, FormationSession $formationSession): RedirectResponse
    {
        $membre = $request->user()->membreEquipe;
        abort_unless($membre->peut(Donnee::Formations, Action::Supprimer), 403);

        $formationSession->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Session supprimée.']);

        return back();
    }

    private function storeImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $path = $request->file('image')->store('formations', 'public');

        abort_if($path === false, 500, "Échec de l'enregistrement de l'image.");

        return Storage::disk('public')->url($path);
    }
}
