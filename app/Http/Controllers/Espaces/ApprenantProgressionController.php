<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use App\Models\Inscription;
use App\Models\Presence;
use App\Models\Seance;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ApprenantProgressionController extends Controller
{
    public function index(Request $request): Response
    {
        $apprenant = $request->user('web')->apprenant;

        $inscriptions = $apprenant->inscriptions()
            ->whereIn('statut', ['VALIDEE', 'FORMEE'])
            ->with(['formation', 'formationSession.seances'])
            ->latest()
            ->get();

        return Inertia::render('espaces/apprenant/progression-index', [
            'apprenant' => $apprenant->espaceHeaderProps(),
            'formations' => $inscriptions->map(function (Inscription $inscription) {
                $seances = $inscription->formationSession->seances ?? new Collection;
                $presences = $this->presences($seances, $inscription);

                return [
                    'inscriptionId' => $inscription->id,
                    'formation' => $inscription->formation->nom,
                    'statut' => $inscription->statut,
                    'progression' => $this->progression($seances, $presences),
                    'seancesTotal' => $seances->count(),
                ];
            }),
        ]);
    }

    public function show(Request $request, Inscription $inscription): Response
    {
        Gate::authorize('view', $inscription);

        $apprenant = $request->user('web')->apprenant;
        $inscription->load(['formation', 'formationSession.seances']);

        $seances = $inscription->formationSession->seances ?? new Collection;
        $presences = $this->presences($seances, $inscription);

        $seancesMappees = $seances
            ->sortBy('date')
            ->map(fn (Seance $seance) => [
                'id' => $seance->id,
                'titre' => $seance->titre,
                'date' => $this->formatDate($seance->date),
                'present' => $presences->get($seance->id)?->present,
            ])
            ->values();

        return Inertia::render('espaces/apprenant/progression-show', [
            'apprenant' => $apprenant->espaceHeaderProps(),
            'formation' => $inscription->formation->nom,
            'progression' => $this->progression($seances, $presences),
            'stats' => [
                'suivies' => $seances->filter(fn (Seance $seance) => $presences->get($seance->id)?->present === true)->count(),
                'manquees' => $seances->filter(fn (Seance $seance) => $presences->get($seance->id)?->present === false)->count(),
                'aVenir' => $seances->filter(fn (Seance $seance) => ! $presences->has($seance->id))->count(),
            ],
            'seances' => $seancesMappees,
        ]);
    }

    /**
     * @param  Collection<int, Seance>  $seances
     * @return BaseCollection<string, Presence>
     */
    private function presences(Collection $seances, Inscription $inscription): BaseCollection
    {
        return Presence::whereIn('seance_id', $seances->pluck('id'))
            ->where('inscription_id', $inscription->id)
            ->get()
            ->keyBy('seance_id');
    }

    /**
     * @param  Collection<int, Seance>  $seances
     * @param  BaseCollection<string, Presence>  $presences
     */
    private function progression(Collection $seances, BaseCollection $presences): int
    {
        if ($seances->isEmpty()) {
            return 0;
        }

        $presentes = $seances->filter(fn (Seance $seance) => $presences->get($seance->id)?->present === true)->count();

        return (int) round($presentes / $seances->count() * 100);
    }

    /**
     * Normalizes a `date`-cast attribute to a Y-m-d string. Explicit
     * rather than a nullsafe `?->toDateString()` because Larastan's
     * schema-derived column type for `date` (as opposed to `datetime`)
     * loses the Carbon type, so this is statically `string` here.
     */
    private function formatDate(?string $value): ?string
    {
        return $value === null ? null : Carbon::parse($value)->toDateString();
    }
}
