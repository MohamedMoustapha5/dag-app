<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use App\Models\Demande;
use App\Models\Formule;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ClientDemandeController extends Controller
{
    public function index(Request $request): Response
    {
        $client = $request->user()->client;

        $demandes = $client->demandes()
            ->with(['service', 'formule'])
            ->latest('updated_at')
            ->get();

        return Inertia::render('espaces/client/demandes-index', [
            'client' => $client->espaceHeaderProps(),
            'demandes' => $demandes->map(fn (Demande $demande) => [
                'id' => $demande->id,
                'reference' => $demande->reference,
                'objet' => $demande->libelleObjet(),
                'statut' => $demande->statut,
                'statutLabel' => $demande->libelleStatutPartenaire(),
                'estPartenaire' => $demande->est_partenaire_nova_tech,
                'updatedAt' => $demande->updated_at,
            ]),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('espaces/client/demandes-create', [
            'client' => $request->user()->client->espaceHeaderProps(),
            'services' => Service::publies()->orderBy('nom')->get(['id', 'nom', 'categorie']),
            'formules' => Formule::where('statut', 'PUBLIEE')->orderBy('nom')->get(['id', 'nom', 'prix_affiche']),
            'serviceSelectionne' => $request->query('service'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['service', 'formule'])],
            'service_id' => [
                'nullable', 'uuid', 'required_if:type,service',
                Rule::exists('services', 'id')->where('statut', 'PUBLIE'),
            ],
            'formule_id' => [
                'nullable', 'uuid', 'required_if:type,formule',
                Rule::exists('formules', 'id')->where('statut', 'PUBLIEE'),
            ],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        $demande = $request->user()->client->demandes()->create([
            'service_id' => $data['type'] === 'service' ? $data['service_id'] : null,
            'formule_id' => $data['type'] === 'formule' ? $data['formule_id'] : null,
            'description' => $data['description'],
            'statut' => 'EN_ETUDE',
        ]);

        return to_route('espace-client.demandes.show', $demande);
    }

    public function show(Request $request, Demande $demande): Response
    {
        Gate::authorize('view', $demande);

        $demande->load(['service', 'formule', 'contrat', 'facture']);

        $etapeActuelle = array_search($demande->statut, Demande::SUIVI_STEPS, true);

        return Inertia::render('espaces/client/demandes-show', [
            'client' => $request->user()->client->espaceHeaderProps(),
            'demande' => [
                'id' => $demande->id,
                'reference' => $demande->reference,
                'objet' => $demande->libelleObjet(),
                'description' => $demande->description,
                'statut' => $demande->statut,
                'montantNegocie' => $demande->montant_negocie,
                'createdAt' => $demande->created_at,
                'contrat' => $demande->contrat ? [
                    'reference' => $demande->contrat->reference,
                    'statut' => $demande->contrat->statut,
                    'fichierPdf' => $demande->contrat->fichier_pdf,
                ] : null,
                'facture' => $demande->facture ? [
                    'reference' => $demande->facture->reference,
                    'statut' => $demande->facture->statut,
                    'montant' => $demande->facture->montant,
                    'montantPaye' => $demande->facture->montant_paye,
                ] : null,
            ],
            'suivi' => [
                'etapes' => Demande::SUIVI_STEPS,
                'etapeActuelle' => $etapeActuelle === false ? null : $etapeActuelle,
                'refusee' => $demande->statut === 'REFUSEE',
            ],
        ]);
    }
}
