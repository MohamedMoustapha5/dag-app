<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
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

        return Inertia::render('espaces/apprenant/dossier', [
            'apprenant' => $apprenant->espaceHeaderProps(),
            'dossier' => [
                'ville' => $apprenant->ville,
                'telephone' => $apprenant->telephone,
                'dateNaissance' => $this->formatDate($apprenant->date_naissance),
                'cvNomFichier' => $apprenant->cv_path !== null ? basename($apprenant->cv_path) : null,
                'consentementCv' => $apprenant->consentement_cv,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $apprenant = $request->user()->apprenant;

        $data = $request->validate([
            'ville' => ['nullable', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'date_naissance' => ['nullable', 'date', 'before:today'],
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
