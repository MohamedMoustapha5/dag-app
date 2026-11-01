<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use App\Models\Apprenant;
use App\Models\Inscription;
use App\Models\Presence;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ApprenantDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $apprenant = $request->user()->apprenant;

        $inscriptionActive = $apprenant->inscriptions()
            ->whereIn('statut', ['VALIDEE', 'FORMEE'])
            ->with(['formation', 'formationSession.seances'])
            ->latest()
            ->first();

        $derniereAttestation = $apprenant->attestations()
            ->with('formation')
            ->latest('date_emission')
            ->first();

        return Inertia::render('espaces/apprenant-dashboard', [
            'apprenant' => $apprenant->espaceHeaderProps(),
            'stats' => $this->stats($apprenant, $inscriptionActive),
            'formationDetail' => $inscriptionActive === null ? null : [
                'nom' => $inscriptionActive->formation->nom,
                'ville' => $inscriptionActive->formationSession?->ville,
                'mode' => $inscriptionActive->formationSession?->mode,
                'dateDebut' => $this->formatDate($inscriptionActive->formationSession?->date_debut),
                'dateFin' => $this->formatDate($inscriptionActive->formationSession?->date_fin),
            ],
            'attestation' => $derniereAttestation === null ? null : [
                'formation' => $derniereAttestation->formation->nom,
                'dateEmission' => $this->formatDate($derniereAttestation->date_emission),
                'seancesRestantes' => $this->seancesRestantes($inscriptionActive),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function stats(Apprenant $apprenant, ?Inscription $inscriptionActive): array
    {
        return [
            'formationEnCours' => $inscriptionActive?->formation->nom,
            'progression' => $this->progression($inscriptionActive),
            'candidaturesEnvoyees' => $apprenant->candidatures()->count(),
            'seancesRestantes' => $this->seancesRestantes($inscriptionActive),
        ];
    }

    private function progression(?Inscription $inscription): int
    {
        $seances = $inscription?->formationSession?->seances;

        if ($seances === null || $seances->isEmpty()) {
            return 0;
        }

        $presentes = Presence::whereIn('seance_id', $seances->pluck('id'))
            ->where('inscription_id', $inscription->id)
            ->where('present', true)
            ->count();

        return (int) round($presentes / $seances->count() * 100);
    }

    private function seancesRestantes(?Inscription $inscription): int
    {
        $seances = $inscription?->formationSession?->seances;

        if ($seances === null) {
            return 0;
        }

        return $seances->where('date', '>=', now()->toDateString())->count();
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
