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

        $formations = Formation::query()
            ->withCount('sessions')
            ->orderBy('nom')
            ->get();

        return Inertia::render('espaces/admin-formations', [
            ...$this->adminEspaceProps($membre),
            'peutCreer' => $membre->peut(Donnee::Formations, Action::Creer),
            'formations' => $formations->map(fn (Formation $formation) => [
                'id' => $formation->id,
                'nom' => $formation->nom,
                'categorie' => $formation->categorie,
                'statut' => $formation->statut,
                'sessionsCount' => $formation->sessions_count,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $membre = $request->user()->membreEquipe;
        abort_unless($membre->peut(Donnee::Formations, Action::Creer), 403);

        $data = $request->validate([
            'nom' => ['required', 'string', 'max:191'],
            'description' => ['required', 'string'],
            'categorie' => ['nullable', 'string', 'max:191'],
            'duree' => ['nullable', 'string', 'max:191'],
            'prerequis' => ['nullable', 'string', 'max:500'],
        ]);

        $formation = Formation::create([
            ...$data,
            'statut' => 'BROUILLON',
        ]);

        return to_route('espace-admin.formations.show', $formation);
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
            'image' => ['nullable', 'string', 'max:255'],
        ]);

        $formation->sessions()->create([
            ...$data,
            'statut' => 'BROUILLON',
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
            'image' => ['nullable', 'string', 'max:255'],
        ]);

        $formationSession->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Session mise à jour.']);

        return back();
    }
}
