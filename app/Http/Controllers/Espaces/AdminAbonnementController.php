<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\Abonnement;
use App\Models\Client;
use App\Models\Formule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminAbonnementController extends Controller
{
    use SharesAdminEspaceProps;

    public function index(Request $request): Response
    {
        $membre = $request->user()->membreEquipe;
        abort_unless($membre->peut(Donnee::Abonnements, Action::Voir), 403);

        $abonnements = Abonnement::query()
            ->with(['client', 'entreprise', 'formule'])
            ->latest('date_souscription')
            ->get();

        return Inertia::render('espaces/admin-abonnements', [
            ...$this->adminEspaceProps($membre),
            'peutCreer' => $membre->peut(Donnee::Abonnements, Action::Creer),
            'peutModifier' => $membre->peut(Donnee::Abonnements, Action::Modifier),
            'abonnements' => $abonnements->map(fn (Abonnement $abonnement) => [
                'id' => $abonnement->id,
                'client' => $this->nomSouscripteur($abonnement),
                'formule' => $abonnement->formule->nom,
                'montant' => $abonnement->montant_negocie,
                'echeance' => $abonnement->echeance?->toDateString(),
                'statut' => $abonnement->statut,
                'progression' => $abonnement->progressionCycle(),
            ]),
            'clients' => Client::query()->orderBy('nom')->get(['id', 'nom', 'type']),
            'formules' => Formule::query()->where('statut', 'PUBLIEE')->orderBy('nom')->get(['id', 'nom']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $membre = $request->user()->membreEquipe;
        abort_unless($membre->peut(Donnee::Abonnements, Action::Creer), 403);

        $data = $request->validate([
            'client_id' => ['required', 'uuid', 'exists:clients,id'],
            'formule_id' => ['required', 'uuid', 'exists:formules,id'],
            'montant_negocie' => ['required', 'numeric', 'min:0'],
            'date_souscription' => ['required', 'date'],
            'cycle_debut' => ['nullable', 'date'],
            'echeance' => ['nullable', 'date', 'after_or_equal:cycle_debut'],
        ]);

        Abonnement::create([...$data, 'statut' => 'BROUILLON']);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Abonnement créé.']);

        return back();
    }

    public function update(Request $request, Abonnement $abonnement): RedirectResponse
    {
        $membre = $request->user()->membreEquipe;
        abort_unless($membre->peut(Donnee::Abonnements, Action::Modifier), 403);

        $data = $request->validate([
            'statut' => ['required', Rule::in(['BROUILLON', 'ACTIF', 'SUSPENDU', 'TERMINE'])],
            'cycle_debut' => ['nullable', 'date'],
            'echeance' => ['nullable', 'date', 'after_or_equal:cycle_debut'],
        ]);

        $abonnement->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Abonnement mis à jour.']);

        return back();
    }

    private function nomSouscripteur(Abonnement $abonnement): string
    {
        if ($abonnement->client_id !== null) {
            return $abonnement->client->nom;
        }

        if ($abonnement->entreprise_id !== null) {
            return $abonnement->entreprise->nom;
        }

        return '—';
    }
}
