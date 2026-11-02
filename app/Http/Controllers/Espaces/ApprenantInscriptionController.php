<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use App\Models\Inscription;
use App\Models\Presence;
use App\Models\Seance;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ApprenantInscriptionController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $apprenant = $request->user('web')->apprenant;

        $inscriptions = $apprenant->inscriptions()
            ->with(['formation', 'formationSession.seances'])
            ->latest()
            ->get();

        return Inertia::render('espaces/apprenant/inscriptions-index', [
            'apprenant' => $apprenant->espaceHeaderProps(),
            'inscriptions' => $inscriptions->map(fn (Inscription $inscription) => $this->mapInscription($inscription))->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapInscription(Inscription $inscription): array
    {
        $seances = $inscription->formationSession?->seances;
        $dateDebut = $inscription->formationSession?->date_debut;

        return [
            'id' => $inscription->id,
            'formation' => $inscription->formation->nom,
            'ville' => $inscription->formationSession?->ville,
            'mode' => $inscription->formationSession?->mode,
            'dateDebut' => $this->formatDate($dateDebut),
            'dateFin' => $this->formatDate($inscription->formationSession?->date_fin),
            'statut' => $inscription->statut,
            'progression' => $this->progression($inscription, $seances),
            'semainesAvantDebut' => $this->semainesAvantDebut($dateDebut),
        ];
    }

    /**
     * @param  Collection<int, Seance>|null  $seances
     */
    private function progression(Inscription $inscription, ?Collection $seances): int
    {
        if ($seances === null || $seances->isEmpty()) {
            return 0;
        }

        $presentes = Presence::whereIn('seance_id', $seances->pluck('id'))
            ->where('inscription_id', $inscription->id)
            ->where('present', true)
            ->count();

        return (int) round($presentes / $seances->count() * 100);
    }

    private function semainesAvantDebut(?string $dateDebut): ?int
    {
        if ($dateDebut === null) {
            return null;
        }

        $debut = Carbon::parse($dateDebut);

        if ($debut->isPast()) {
            return null;
        }

        return (int) ceil(now()->diffInDays($debut) / 7);
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
