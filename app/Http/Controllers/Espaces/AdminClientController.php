<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Apprenant;
use App\Models\Client;
use App\Notifications\PartenariatValideNotification;
use App\Support\Notifications\Notifier;
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
            ->with(['user', 'entreprisePartenaire'])
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
                'statutPartenaire' => $client->entreprisePartenaire?->statut,
                'demandesCount' => $client->demandes_count,
                'inscritLe' => $client->created_at?->format('d/m/Y'),
            ]),
            'actions' => [
                'supprimer' => $membre->peut(Donnee::DemandesClients, Action::Supprimer),
                'supprimerApprenant' => $membre->peut(Donnee::DossiersApprenants, Action::Supprimer),
                'nommerPartenaire' => $membre->peut(Donnee::EntreprisesOffres, Action::Valider),
            ],
        ]);
    }

    /**
     * Makes an Entreprise client an official recruitment partner without a
     * request on its part: its partner record is created from the account
     * (or an existing one reactivated) and its espace unlocks job offers and
     * candidates at once.
     */
    public function nommerPartenaire(Request $request, Client $client): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::EntreprisesOffres, Action::Valider), 403);
        abort_unless($client->estStructure(), 422, 'Seul un compte entreprise ou organisation peut devenir partenaire recrutement.');

        if ($client->estPartenaireValide()) {
            return back();
        }

        $partenaire = $client->entreprisePartenaire()->firstOrNew();
        $partenaire->fill($partenaire->exists ? ['statut' => 'ACTIF'] : [
            'nom' => $client->nom,
            'secteur' => $client->secteur_activite,
            'email' => $client->user->email,
            'telephone' => $client->telephone,
            'statut' => 'ACTIF',
        ])->save();

        ActivityLog::record($membre, 'a validé le partenaire', $partenaire, "{$partenaire->nom}, depuis son compte client.");

        Notifier::envoyer($client->user, new PartenariatValideNotification);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$client->nom} est maintenant un partenaire validé : ses offres et candidatures sont ouvertes.",
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
