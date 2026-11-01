<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use App\Models\Apprenant;
use App\Models\Inscription;
use App\Models\Presence;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApprenantDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $apprenant = $request->user()->apprenant;

        return Inertia::render('espaces/apprenant-dashboard', [
            'apprenant' => $apprenant->espaceHeaderProps(),
            'stats' => $this->stats($apprenant),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function stats(Apprenant $apprenant): array
    {
        $inscriptionActive = $apprenant->inscriptions()
            ->whereIn('statut', ['VALIDEE', 'FORMEE'])
            ->with(['formation', 'formationSession.seances'])
            ->latest()
            ->first();

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
}
