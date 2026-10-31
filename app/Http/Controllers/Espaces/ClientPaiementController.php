<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use App\Models\Facture;
use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ClientPaiementController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $client = $request->user()->client;

        $factureIds = $client->enregistrementsClientOuEntreprise(Facture::class)->pluck('id');

        $paiements = Paiement::query()
            ->whereIn('facture_id', $factureIds)
            ->with('facture')
            ->latest('date_paiement')
            ->get();

        return Inertia::render('espaces/client/paiements-index', [
            'client' => $client->espaceHeaderProps(),
            'paiements' => $paiements->map(fn (Paiement $paiement) => [
                'id' => $paiement->id,
                'factureReference' => $paiement->facture->reference,
                'montant' => (string) $paiement->montant,
                'moyen' => $paiement->moyen,
                'statut' => $paiement->statut,
                'datePaiement' => $this->formatDateTime($paiement->date_paiement),
            ]),
        ]);
    }

    /**
     * Normalizes a `datetime`-cast attribute to an ISO 8601 string.
     * Explicit rather than a nullsafe `?->toIso8601String()` — Larastan
     * doesn't always resolve a model's cast type to Carbon on first
     * encounter (see the same note on ClientDashboardController).
     */
    private function formatDateTime(?string $value): ?string
    {
        return $value === null ? null : Carbon::parse($value)->toIso8601String();
    }
}
