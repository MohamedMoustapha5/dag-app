<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use App\Models\Inscription;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ApprenantInscriptionController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $apprenant = $request->user()->apprenant;

        $inscriptions = $apprenant->inscriptions()
            ->with(['formation', 'formationSession', 'facture'])
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
        return [
            'id' => $inscription->id,
            'formation' => $inscription->formation->nom,
            'ville' => $inscription->formationSession?->ville,
            'mode' => $inscription->formationSession?->mode,
            'dateDebut' => $inscription->formationSession?->date_debut?->toDateString(),
            'statut' => $inscription->statut,
            'montant' => $inscription->montant_negocie !== null ? (string) $inscription->montant_negocie : null,
            'facture' => $inscription->facture === null ? null : [
                'reference' => $inscription->facture->reference,
                'montant' => (string) $inscription->facture->montant,
                'montantPaye' => (string) $inscription->facture->montant_paye,
                'statut' => $inscription->facture->statut,
                'echeance' => $this->formatDate($inscription->facture->echeance),
            ],
        ];
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
