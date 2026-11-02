<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\FormationSession;
use App\Models\Seance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AdminSeanceController extends Controller
{
    use SharesAdminEspaceProps;

    public function show(Request $request, FormationSession $formationSession): Response
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::Formations, Action::Voir), 403);

        $formationSession->load(['formation', 'seances' => fn ($query) => $query->orderBy('date')]);

        return Inertia::render('espaces/admin-session-show', [
            ...$this->adminEspaceProps($membre),
            'peutModifier' => $membre->peut(Donnee::Formations, Action::Modifier),
            'peutCreer' => $membre->peut(Donnee::Formations, Action::Creer),
            'session' => [
                'id' => $formationSession->id,
                'formationId' => $formationSession->formation_id,
                'formationNom' => $formationSession->formation->nom,
                'ville' => $formationSession->ville,
                'mode' => $formationSession->mode,
                'lieu' => $formationSession->lieu,
                'formateur' => $formationSession->formateur,
                'dateDebut' => $formationSession->date_debut?->toDateString(),
                'dateFin' => $formationSession->date_fin?->toDateString(),
                'places' => $formationSession->places,
                'statut' => $formationSession->statut,
                'image' => $formationSession->image,
            ],
            'seances' => $formationSession->seances->map(fn (Seance $seance) => [
                'id' => $seance->id,
                'titre' => $seance->titre,
                'date' => $this->formatDate($seance->date),
            ]),
        ]);
    }

    public function store(Request $request, FormationSession $formationSession): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::Formations, Action::Creer), 403);

        $data = $request->validate([
            'titre' => ['nullable', 'string', 'max:191'],
            'date' => ['required', 'date'],
        ]);

        $formationSession->seances()->create($data);

        return back();
    }

    public function destroy(Request $request, Seance $seance): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::Formations, Action::Supprimer), 403);

        $seance->delete();

        return back();
    }

    /**
     * Normalizes a `date`-cast attribute to a Y-m-d string. Explicit
     * rather than a nullsafe `?->toDateString()` because Larastan's
     * schema-derived column type for `date` (as opposed to `datetime`)
     * loses the Carbon type, so this is statically `string` here.
     */
    private function formatDate(string $value): string
    {
        return Carbon::parse($value)->toDateString();
    }
}
