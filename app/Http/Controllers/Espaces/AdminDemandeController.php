<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\Demande;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminDemandeController extends Controller
{
    use SharesAdminEspaceProps;

    public function index(Request $request): Response
    {
        $membre = $request->user()->membreEquipe;
        abort_unless($membre->peut(Donnee::DemandesClients, Action::Voir), 403);

        $demandes = Demande::query()
            ->with(['client', 'entreprise', 'service', 'formule'])
            ->latest('updated_at')
            ->get();

        return Inertia::render('espaces/admin-demandes', [
            ...$this->adminEspaceProps($membre),
            'peutModifier' => $membre->peut(Donnee::DemandesClients, Action::Modifier),
            'demandes' => $demandes->map(fn (Demande $demande) => [
                'id' => $demande->id,
                'reference' => $demande->reference,
                'client' => $this->nomClient($demande),
                'objet' => $demande->libelleObjet(),
                'statut' => $demande->statut,
                'montantNegocie' => $demande->montant_negocie,
                'updatedAt' => $demande->updated_at?->toDateString(),
            ]),
        ]);
    }

    public function show(Request $request, Demande $demande): Response
    {
        $membre = $request->user()->membreEquipe;
        abort_unless($membre->peut(Donnee::DemandesClients, Action::Voir), 403);

        $demande->load(['client', 'entreprise', 'service', 'formule', 'contrat', 'facture']);

        $etapeActuelle = array_search($demande->statut, Demande::SUIVI_STEPS, true);

        return Inertia::render('espaces/admin-demande-show', [
            ...$this->adminEspaceProps($membre),
            'peutModifier' => $membre->peut(Donnee::DemandesClients, Action::Modifier),
            'demande' => [
                'id' => $demande->id,
                'reference' => $demande->reference,
                'client' => $this->nomClient($demande),
                'clientTelephone' => $demande->client_id !== null ? $demande->client->telephone : null,
                'objet' => $demande->libelleObjet(),
                'description' => $demande->description,
                'statut' => $demande->statut,
                'montantNegocie' => $demande->montant_negocie,
                'createdAt' => $demande->created_at?->toDateString(),
                'contrat' => $demande->contrat !== null ? [
                    'reference' => $demande->contrat->reference,
                    'statut' => $demande->contrat->statut,
                ] : null,
                'facture' => $demande->facture !== null ? [
                    'reference' => $demande->facture->reference,
                    'statut' => $demande->facture->statut,
                ] : null,
            ],
            'suivi' => [
                'etapes' => Demande::SUIVI_STEPS,
                'etapeActuelle' => $etapeActuelle === false ? null : $etapeActuelle,
                'refusee' => $demande->statut === 'REFUSEE',
            ],
        ]);
    }

    public function update(Request $request, Demande $demande): RedirectResponse
    {
        $membre = $request->user()->membreEquipe;
        abort_unless($membre->peut(Donnee::DemandesClients, Action::Modifier), 403);

        $data = $request->validate([
            'statut' => ['required', Rule::in([...Demande::SUIVI_STEPS, 'REFUSEE'])],
            'montant_negocie' => ['nullable', 'numeric', 'min:0'],
        ]);

        $demande->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Demande mise à jour.']);

        return back();
    }

    /**
     * Mirrors Demande::libelleObjet()'s convention of branching on the FK
     * column rather than nullsafe-chaining the relation: Larastan can't
     * see that client_id/entreprise_id are nullable from the BelongsTo
     * return type alone, so it would otherwise (wrongly) flag `?->` here
     * as unnecessary — removing it would be a real null-pointer risk,
     * since exactly one of the two is actually set per demande.
     */
    private function nomClient(Demande $demande): string
    {
        if ($demande->client_id !== null) {
            return $demande->client->nom;
        }

        if ($demande->entreprise_id !== null) {
            return $demande->entreprise->nom;
        }

        return '—';
    }
}
