<?php

namespace App\Http\Controllers\Espaces;

use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\Demande;
use App\Models\Formule;
use App\Models\Service;
use App\Support\Notifications\Notifier;
use App\Support\Paiements\PaiementService;
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
        $client = $request->user('web')->client;

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
            'client' => $request->user('web')->client->espaceHeaderProps(),
            'services' => Service::publies()->orderBy('nom')->get(['id', 'nom', 'categorie']),
            'formules' => Formule::where('statut', 'PUBLIEE')->orderBy('nom')->get(['id', 'nom', 'prix_affiche']),
            'serviceSelectionne' => $request->query('service'),
            'formuleSelectionnee' => $request->query('formule'),
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

        $demande = $request->user('web')->client->demandes()->create([
            'service_id' => $data['type'] === 'service' ? $data['service_id'] : null,
            'formule_id' => $data['type'] === 'formule' ? $data['formule_id'] : null,
            'description' => $data['description'],
            'statut' => 'EN_ETUDE',
        ]);

        Notifier::alerterEquipe(
            Donnee::DemandesClients,
            Action::Modifier,
            'demande.nouvelle',
            "Nouvelle demande — {$demande->reference}",
            "{$request->user('web')->client->nom} a demandé : {$demande->libelleObjet()}.",
            route('espace-admin.demandes.show', $demande),
        );

        return to_route('espace-client.demandes.show', $demande);
    }

    public function show(Request $request, Demande $demande, PaiementService $paiements): Response
    {
        Gate::authorize('view', $demande);

        $demande->load(['service', 'formule', 'contrat', 'facture']);

        $etapeActuelle = array_search($demande->statut, Demande::SUIVI_STEPS, true);

        return Inertia::render('espaces/client/demandes-show', [
            'client' => $request->user('web')->client->espaceHeaderProps(),
            'demande' => [
                'id' => $demande->id,
                'reference' => $demande->reference,
                'objet' => $demande->libelleObjet(),
                'description' => $demande->description,
                'statut' => $demande->statut,
                'montantNegocie' => $demande->montant_negocie,
                'messageDevis' => $demande->message_devis,
                'createdAt' => $demande->created_at,
                'contrat' => $demande->contrat && $demande->contrat->statut !== 'BROUILLON' ? [
                    'reference' => $demande->contrat->reference,
                    'statut' => $demande->contrat->statut,
                    'id' => $demande->contrat->id,
                    'estSigne' => $demande->contrat->estSigne(),
                    'envoye' => $demande->contrat->statut === 'ENVOYE',
                ] : null,
                'facture' => $demande->facture ? [
                    'id' => $demande->facture->id,
                    'reference' => $demande->facture->reference,
                    'statut' => $demande->facture->statut,
                    'montant' => $demande->facture->montant,
                    'montantPaye' => $demande->facture->montant_paye,
                    'solde' => (string) $paiements->soldeRestant($demande->facture),
                    'paiementEnAttente' => $paiements->paiementEnAttente($demande->facture),
                    'attendSignatureContrat' => $paiements->attendSignatureContrat($demande->facture),
                    'attendValidationContrat' => $paiements->attendValidationContrat($demande->facture),
                ] : null,
            ],
            'moyensPaiement' => $paiements->moyens(),
            'suivi' => [
                'etapes' => Demande::SUIVI_STEPS,
                'etapeActuelle' => $etapeActuelle === false ? null : $etapeActuelle,
                'refusee' => $demande->statut === 'REFUSEE',
            ],
        ]);
    }
}
