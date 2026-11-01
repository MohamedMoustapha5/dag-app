<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use App\Models\Presence;
use App\Models\Seance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ApprenantDossierController extends Controller
{
    public function edit(Request $request): Response
    {
        $apprenant = $request->user()->apprenant;

        $inscription = $apprenant->inscriptions()
            ->whereIn('statut', ['VALIDEE', 'FORMEE'])
            ->with(['formation', 'formationSession.seances'])
            ->latest()
            ->first();

        $seances = $inscription?->formationSession?->seances;
        $presences = $seances === null ? collect() : Presence::whereIn('seance_id', $seances->pluck('id'))
            ->where('inscription_id', $inscription->id)
            ->get()
            ->keyBy('seance_id');

        return Inertia::render('espaces/apprenant/dossier', [
            'apprenant' => $apprenant->espaceHeaderProps(),
            'dossier' => [
                'ville' => $apprenant->ville,
                'telephone' => $apprenant->telephone,
                'email' => $apprenant->user->email,
                'dateNaissance' => $this->formatDate($apprenant->date_naissance),
                'cvNomFichier' => $apprenant->cv_path !== null ? basename($apprenant->cv_path) : null,
                'consentementCv' => $apprenant->consentement_cv,
            ],
            'formationSuivie' => $inscription === null ? null : [
                'nom' => $inscription->formation->nom,
                'ville' => $inscription->formationSession?->ville,
                'dateDebut' => $this->formatDate($inscription->formationSession?->date_debut),
                'dateFin' => $this->formatDate($inscription->formationSession?->date_fin),
                'seancesCompletees' => $seances?->filter(fn (Seance $seance) => $presences->get($seance->id)?->present === true)->count() ?? 0,
                'seancesTotal' => $seances?->count() ?? 0,
            ],
            'attestation' => [
                'seancesRestantes' => $seances?->where('date', '>=', now()->toDateString())->count() ?? 0,
            ],
            'presences' => $seances === null ? [] : $seances
                ->sortBy('date')
                ->map(fn (Seance $seance) => [
                    'id' => $seance->id,
                    'titre' => $seance->titre,
                    'date' => $this->formatDate($seance->date),
                    'present' => $presences->get($seance->id)?->present,
                ])
                ->values(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $apprenant = $request->user()->apprenant;

        $data = $request->validate([
            'cv' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'consentement_cv' => ['boolean'],
        ]);

        if ($request->hasFile('cv')) {
            $data['cv_path'] = $request->file('cv')->store('apprenants-cv');
        }

        unset($data['cv']);

        $apprenant->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Votre dossier a été mis à jour.']);

        return to_route('espace-apprenant.dossier');
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
