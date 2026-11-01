<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use App\Models\Inscription;
use App\Models\Presence;
use App\Models\Seance;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as BaseCollection;
use Inertia\Inertia;
use Inertia\Response;

class ApprenantDossierController extends Controller
{
    public function edit(Request $request): Response
    {
        $apprenant = $request->user()->apprenant;

        $inscriptions = $apprenant->inscriptions()
            ->whereIn('statut', ['VALIDEE', 'FORMEE'])
            ->with(['formation', 'formationSession.seances'])
            ->latest()
            ->get();

        $attestationsParFormation = $apprenant->attestations()->get()->keyBy('formation_id');

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
            'formations' => $inscriptions->map(function (Inscription $inscription) use ($attestationsParFormation) {
                $seances = $inscription->formationSession->seances ?? new Collection;
                $presences = $this->presences($seances, $inscription);
                $attestation = $attestationsParFormation->get($inscription->formation_id);

                return [
                    'inscriptionId' => $inscription->id,
                    'nom' => $inscription->formation->nom,
                    'ville' => $inscription->formationSession?->ville,
                    'dateDebut' => $this->formatDate($inscription->formationSession?->date_debut),
                    'dateFin' => $this->formatDate($inscription->formationSession?->date_fin),
                    'statut' => $inscription->statut,
                    'progression' => $this->progression($seances, $presences),
                    'seancesTotal' => $seances->count(),
                    'seancesRestantes' => $seances->where('date', '>=', now()->toDateString())->count(),
                    'attestation' => [
                        // L'attestation n'est jamais générée automatiquement :
                        // seule son existence, créée par un membre DAG
                        // habilité (espace-admin/attestations), vaut
                        // validation (Donnee::DossiersApprenants, Action::Valider).
                        'validee' => $attestation !== null,
                        'dateEmission' => $attestation === null ? null : $this->formatDate($attestation->date_emission),
                    ],
                ];
            }),
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
