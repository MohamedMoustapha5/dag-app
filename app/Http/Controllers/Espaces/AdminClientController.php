<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Apprenant;
use App\Models\Client;
use App\Support\SuppressionCompte;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return Inertia::render('espaces/admin-clients', [
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
                'demandesCount' => $client->demandes_count,
                'inscritLe' => $client->created_at?->format('d/m/Y'),
            ]),
            'actions' => [
                'supprimer' => $membre->peut(Donnee::DemandesClients, Action::Supprimer),
                'supprimerApprenant' => $membre->peut(Donnee::DossiersApprenants, Action::Supprimer),
            ],
        ]);
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
