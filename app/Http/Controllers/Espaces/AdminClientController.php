<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Apprenant;
use App\Models\Client;
use App\Support\Recrutement\AbonnementRecrutement;
use App\Support\SuppressionCompte;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminClientController extends Controller
{
    use SharesAdminEspaceProps;

    public function index(Request $request): Response
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::DemandesClients, Action::Voir), 403);

        $clients = Client::query()
            ->with('user')
            ->withMax(['accesRecrutement as fin_acces_recrutement' => fn ($query) => $query
                ->where('statut', 'ACTIF')
                ->whereDate('fin', '>=', now()->toDateString())], 'fin')
            ->withExists(['partenariats as partenaire_officiel' => fn ($query) => $query->enVigueur()])
            ->withCount('demandes')
            ->orderByDesc('created_at')
            ->get();

        $apprenants = $membre->peut(Donnee::DossiersApprenants, Action::Voir)
            ? Apprenant::query()
                ->whereDoesntHave('candidatureSpontanee')
                ->with(['user', 'inscriptions', 'attestations'])
                ->get()
                ->sortByDesc(fn (Apprenant $apprenant) => $apprenant->user->created_at)
                ->values()
                ->map(fn (Apprenant $apprenant) => [
                    'id' => $apprenant->id,
                    'nom' => $apprenant->user->name,
                    'email' => $apprenant->user->email,
                    'telephone' => $apprenant->telephone,
                    'ville' => $apprenant->ville,
                    'estForme' => $apprenant->estForme(),
                    'inscriptionsCount' => $apprenant->inscriptions->count(),
                    'inscritLe' => $apprenant->user->created_at?->format('d/m/Y'),
                ])
            : null;

        return Inertia::render('espaces/admin/clients', [
            ...$this->adminEspaceProps($membre),
            'apprenants' => $apprenants,
            'clients' => $clients->map(fn (Client $client) => [
                'id' => $client->id,
                'nom' => $client->nom,
                'email' => $client->user->email,
                'type' => $client->type,
                'typeLabel' => $client->typeLabel(),
                'ville' => $client->ville,
                'telephone' => $client->telephone,
                'statut' => $client->statut,
                'partenaireOfficiel' => (bool) $client->partenaire_officiel,
                'finAccesRecrutement' => $client->fin_acces_recrutement
                    ? Carbon::parse($client->fin_acces_recrutement)->toDateString()
                    : null,
                'demandesCount' => $client->demandes_count,
                'inscritLe' => $client->created_at?->format('d/m/Y'),
            ]),
            'actions' => [
                'supprimer' => $membre->peut(Donnee::DemandesClients, Action::Supprimer),
                'supprimerApprenant' => $membre->peut(Donnee::DossiersApprenants, Action::Supprimer),
                'offrirAcces' => $membre->peut(Donnee::EntreprisesOffres, Action::Valider),
            ],
        ]);
    }

    /**
     * DAG offers an entreprise or an organisation a period of recruitment
     * access, without payment (a partner, a trial). It follows any period
     * already secured, like a renewal.
     */
    public function offrirAccesRecrutement(Request $request, Client $client, AbonnementRecrutement $abonnements): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::EntreprisesOffres, Action::Valider), 403);
        abort_unless($client->estStructure(), 422, 'Seul un compte entreprise ou organisation peut accéder au recrutement.');

        $data = $request->validate([
            'mois' => ['required', 'integer', Rule::in([1, 3, 6, 12])],
        ]);

        $acces = $abonnements->offrir($client, (int) $data['mois'], $membre);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Accès recrutement offert à {$client->nom} jusqu’au {$acces->fin->format('d/m/Y')}.",
        ]);

        return back();
    }

    public function destroy(Request $request, Client $client): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::DemandesClients, Action::Supprimer), 403);

        $nom = $client->nom;
        $anonymise = SuppressionCompte::fermer($client->user);

        ActivityLog::record(
            $membre,
            $anonymise ? 'a anonymisé le compte client' : 'a supprimé le compte client',
            null,
            $anonymise ? "Compte de {$nom} : données personnelles effacées, factures et contrats conservés." : "Compte de {$nom}.",
        );

        return back();
    }
}
