<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Partenariat;
use App\Support\Partenariats\GestionPartenariats;
use App\Support\Partenariats\ParcoursPartenariat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The partnerships seen by DAG: requests to study, contracts out for
 * signature or to validate, partners in force, and proposals DAG makes on
 * its own initiative.
 */
class AdminPartenariatController extends Controller
{
    use SharesAdminEspaceProps;

    public function index(Request $request): Response
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::EntreprisesOffres, Action::Voir), 403);

        $partenariats = Partenariat::query()
            ->with(['client.user', 'contrat', 'traitePar'])
            ->orderByRaw("statut = 'EN_ETUDE' desc")
            ->latest()
            ->get();

        $peutGerer = $membre->peut(Donnee::EntreprisesOffres, Action::Valider);

        return Inertia::render('espaces/admin/partenariats', [
            ...$this->adminEspaceProps($membre),
            'partenariats' => $partenariats->map(fn (Partenariat $partenariat) => [
                'id' => $partenariat->id,
                'reference' => $partenariat->reference,
                'origine' => $partenariat->origine,
                'etape' => $partenariat->etape(),
                'parcours' => ParcoursPartenariat::pour($partenariat, vueClient: false),
                'clientNom' => $partenariat->client->nom,
                'clientEmail' => $partenariat->client->user->email,
                'clientTypeLabel' => $partenariat->client->typeLabel(),
                'clientSecteur' => $partenariat->client->secteur_activite,
                'domaines' => $partenariat->domaines,
                'message' => $partenariat->message,
                'duree' => $partenariat->dureeAffichee(),
                'debut' => $partenariat->debut?->toDateString(),
                'fin' => $partenariat->fin?->toDateString(),
                'motifRefus' => $partenariat->motif_refus,
                'traitePar' => $partenariat->traitePar?->name,
                'creeLe' => $partenariat->created_at?->toDateString(),
                'contrat' => $partenariat->contrat !== null ? [
                    'id' => $partenariat->contrat->id,
                    'reference' => $partenariat->contrat->reference,
                ] : null,
            ]),
            'domaines' => collect(Partenariat::DOMAINES)
                ->map(fn (string $libelle, string $code) => ['code' => $code, 'libelle' => $libelle])
                ->values(),
            'durees' => Partenariat::DUREES,
            'clientsProposables' => $peutGerer ? $this->clientsProposables() : [],
            'actions' => [
                'gerer' => $peutGerer,
                'validerContrat' => $membre->peut(Donnee::ContratsClauses, Action::Valider),
                'voirContrat' => $membre->peut(Donnee::ContratsClauses, Action::Voir),
            ],
        ]);
    }

    public function accepter(Request $request, Partenariat $partenariat, GestionPartenariats $partenariats): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::EntreprisesOffres, Action::Valider), 403);

        $data = $request->validate([
            'duree_mois' => ['required', 'integer', Rule::in(Partenariat::DUREES)],
        ]);

        $partenariats->accepter($partenariat, (int) $data['duree_mois'], $membre);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Demande acceptée : le contrat de partenariat est envoyé à {$partenariat->client->nom} pour signature.",
        ]);

        return back();
    }

    public function refuser(Request $request, Partenariat $partenariat, GestionPartenariats $partenariats): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::EntreprisesOffres, Action::Valider), 403);

        $data = $request->validate([
            'motif' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'motif.required' => 'Indiquez le motif du refus : il est transmis à la structure.',
            'motif.min' => 'Expliquez le refus en une phrase au moins.',
        ]);

        $partenariats->refuser($partenariat, $data['motif'], $membre);

        Inertia::flash('toast', ['type' => 'info', 'message' => "Demande refusée : {$partenariat->client->nom} est informé du motif."]);

        return back();
    }

    public function proposer(Request $request, GestionPartenariats $partenariats): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::EntreprisesOffres, Action::Valider), 403);

        $data = $request->validate([
            'client_id' => ['required', 'uuid', Rule::exists('clients', 'id')],
            'duree_mois' => ['required', 'integer', Rule::in(Partenariat::DUREES)],
            'domaines' => ['required', 'array', 'min:1'],
            'domaines.*' => ['string', 'distinct', Rule::in(array_keys(Partenariat::DOMAINES))],
            'message' => ['nullable', 'string', 'max:2000'],
        ], [
            'client_id.required' => 'Choisissez la structure à qui proposer le partenariat.',
            'domaines.required' => 'Choisissez au moins un domaine de collaboration.',
            'domaines.min' => 'Choisissez au moins un domaine de collaboration.',
        ]);

        $client = Client::findOrFail($data['client_id']);
        $partenariats->proposer($client, (int) $data['duree_mois'], $data['domaines'], $data['message'] ?? null, $membre);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Partenariat proposé : le contrat est envoyé à {$client->nom} pour signature.",
        ]);

        return back();
    }

    /**
     * Entreprises and organisations without a partnership under way.
     *
     * @return list<array{id: string, nom: string, typeLabel: string}>
     */
    private function clientsProposables(): array
    {
        return Client::query()
            ->whereIn('type', ['ENTREPRISE', 'ORGANISATION'])
            ->whereDoesntHave('partenariats', fn ($query) => $query->ouverts())
            ->orderBy('nom')
            ->get()
            ->map(fn (Client $client) => [
                'id' => $client->id,
                'nom' => $client->nom,
                'typeLabel' => $client->typeLabel(),
            ])
            ->all();
    }
}
