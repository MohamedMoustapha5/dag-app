<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use App\Models\AccesRecrutement;
use App\Models\ForfaitRecrutement;
use App\Support\Paiements\PaiementService;
use App\Support\Recrutement\AbonnementRecrutement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The recruitment subscription of an entreprise or an organisation: the
 * forfaits DAG offers, the access secured so far, and the facture of the
 * forfait chosen, paid online like any facture.
 */
class ClientRecrutementController extends Controller
{
    public function abonnement(Request $request, PaiementService $paiements): Response
    {
        $client = $request->user('web')->client;
        abort_unless($client->estStructure(), 403);

        $fin = $client->finAccesRecrutement();
        $enAttente = $client->accesRecrutement()
            ->where('statut', 'EN_ATTENTE_PAIEMENT')
            ->with('facture')
            ->latest()
            ->first();
        $facture = $enAttente?->facture;

        return Inertia::render('espaces/client/recrutement-abonnement', [
            'client' => $client->espaceHeaderProps(),
            'forfaits' => ForfaitRecrutement::query()->orderBy('ordre')->get()->map(fn (ForfaitRecrutement $forfait) => [
                'id' => $forfait->id,
                'libelle' => $forfait->libelle,
                'dureeMois' => $forfait->duree_mois,
                'prix' => (string) $forfait->prix,
            ]),
            'acces' => [
                'actif' => $client->estPartenaireValide(),
                'fin' => $fin?->toDateString(),
            ],
            'factureEnAttente' => $facture !== null && in_array($facture->statut, ['EN_ATTENTE', 'PARTIELLEMENT_PAYEE'], true) ? [
                'id' => $facture->id,
                'reference' => $facture->reference,
                'objet' => $facture->libelleObjet(),
                'forfait' => $enAttente->libelle,
                'solde' => (string) $paiements->soldeRestant($facture),
                'paiementEnAttente' => $paiements->paiementEnAttente($facture),
            ] : null,
            'historique' => $client->accesRecrutement()
                ->where('statut', 'ACTIF')
                ->orderByDesc('debut')
                ->get()
                ->map(fn (AccesRecrutement $acces) => [
                    'id' => $acces->id,
                    'libelle' => $acces->libelle,
                    'origine' => $acces->origine,
                    'montant' => (string) $acces->montant,
                    'debut' => $acces->debut?->toDateString(),
                    'fin' => $acces->fin?->toDateString(),
                ]),
            'moyensPaiement' => $paiements->moyens(),
        ]);
    }

    /**
     * The client chooses a forfait: its facture is issued (or the one still
     * awaiting payment switched to it), then paid from the same page.
     */
    public function souscrire(Request $request, AbonnementRecrutement $abonnements): RedirectResponse
    {
        $client = $request->user('web')->client;
        abort_unless($client->estStructure(), 403);

        $request->validate([
            'forfait_id' => ['required', 'uuid', Rule::exists('forfaits_recrutement', 'id')],
        ]);

        $facture = $abonnements->souscrire($client, ForfaitRecrutement::findOrFail($request->string('forfait_id')->value()));

        Inertia::flash('payerFacture', $facture->id);

        return back();
    }
}
