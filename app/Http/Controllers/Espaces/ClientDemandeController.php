<?php

namespace App\Http\Controllers\Espaces;

use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Contrat;
use App\Models\Demande;
use App\Models\Formule;
use App\Models\Service;
use App\Notifications\ContratASignerNotification;
use App\Support\ContratRedaction;
use App\Support\Notifications\Notifier;
use App\Support\Paiements\PaiementService;
use App\Support\Parcours\ParcoursDemande;
use App\Support\ProformaPdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
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
            ->with(['service', 'formule', 'contrat', 'facture'])
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
                'prochaineEtape' => self::prochaineEtape($demande),
            ]),
        ]);
    }

    /**
     * The next step of a demande in short, for the list of demandes.
     *
     * @return array{titre: string, aVous: bool}|null
     */
    private static function prochaineEtape(Demande $demande): ?array
    {
        $prochaine = ParcoursDemande::pour($demande, 'client')['prochaine'];

        return $prochaine === null ? null : ['titre' => $prochaine['titre'], 'aVous' => $prochaine['aVous']];
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

        $demande->load(['client', 'service', 'formule', 'contrat', 'facture']);

        $etapeActuelle = array_search($demande->statut, $demande->etapesSuivi(), true);

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
                'estParticulier' => $demande->estParticulier(),
                'devis' => ProformaPdf::disponible($demande) ? [
                    'numero' => ProformaPdf::numero($demande),
                    'valideLe' => $demande->devis_valide_le?->toIso8601String(),
                    'peutValider' => $this->peutValiderDevis($demande),
                ] : null,
                'refusDevis' => $demande->devisEstRefuse() ? [
                    'le' => $demande->devis_refuse_le->toIso8601String(),
                    'motif' => $demande->motif_refus_devis,
                    'abandon' => $demande->statut === 'REFUSEE',
                ] : null,
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
                    'attendValidationDevis' => $paiements->attendValidationDevis($demande->facture),
                ] : null,
            ],
            'moyensPaiement' => $paiements->moyens(),
            'motifsRefusDevis' => collect(Demande::MOTIFS_REFUS_DEVIS)
                ->map(fn (string $libelle, string $valeur) => ['value' => $valeur, 'label' => $libelle])
                ->values(),
            'parcours' => ParcoursDemande::pour($demande, 'client'),
            'suivi' => [
                'etapes' => $demande->etapesSuivi(),
                'etapeActuelle' => $etapeActuelle === false ? null : $etapeActuelle,
                'refusee' => $demande->statut === 'REFUSEE',
            ],
        ]);
    }

    /**
     * The client accepts the devis DAG sent (the proforma). A particulier
     * can then pay. For an entreprise or an organisation, the contract is
     * drafted from the accepted devis and sent at once for signature; DAG
     * validates it once signed, which opens the payment.
     */
    public function validerDevis(Request $request, Demande $demande): RedirectResponse
    {
        Gate::authorize('view', $demande);

        abort_unless($this->peutValiderDevis($demande), 409, 'Ce devis ne peut pas être validé : il a déjà été validé ou n’est plus d’actualité.');

        $particulier = $demande->estParticulier();

        $contrat = DB::transaction(function () use ($demande, $particulier): ?Contrat {
            $demande->update(['devis_valide_le' => now()]);

            return $particulier ? null : ContratRedaction::envoyerAvecDevis($demande);
        });

        $montant = number_format((float) $demande->montant_negocie, 0, ',', ' ');

        ActivityLog::record(null, 'devis validé par le client', $demande, "{$demande->reference} : {$montant} FCFA, validé par {$demande->client->nom}.");

        Notifier::alerterEquipe(
            Donnee::DemandesClients,
            Action::Voir,
            'devis.valide',
            "Devis validé — {$demande->reference}",
            "{$demande->client->nom} a validé le devis de {$montant} FCFA ({$demande->libelleObjet()}) : "
                .($particulier ? 'le paiement est ouvert.' : "le contrat {$contrat?->reference} lui est envoyé pour signature."),
            route('espace-admin.demandes.show', $demande),
        );

        if ($contrat !== null) {
            Notifier::envoyer(Notifier::utilisateurDe($contrat), new ContratASignerNotification($contrat));
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $particulier
                ? 'Devis validé : vous pouvez maintenant régler la facture.'
                : 'Devis validé : votre contrat est prêt, signez-le pour passer au paiement.',
        ]);

        return back();
    }

    /**
     * The client declines the devis, with a reason. Either they want a
     * revised proposal (the demande goes back to DAG, under study) or they
     * do not wish to go on (the demande is closed). DAG is told either way.
     */
    public function refuserDevis(Request $request, Demande $demande): RedirectResponse
    {
        Gate::authorize('view', $demande);

        abort_unless($this->peutValiderDevis($demande), 409, 'Ce devis ne peut plus être refusé : il a déjà été validé ou n’est plus d’actualité.');

        $data = $request->validate([
            'motif' => ['required', Rule::in(array_keys(Demande::MOTIFS_REFUS_DEVIS))],
            'commentaire' => ['nullable', 'string', 'max:1000', 'required_if:motif,AUTRE'],
            'suite' => ['required', Rule::in(['nouveau_devis', 'abandon'])],
        ], [
            'commentaire.required_if' => 'Précisez la raison de votre refus.',
        ]);

        $motif = Demande::MOTIFS_REFUS_DEVIS[$data['motif']]
            .(filled($data['commentaire'] ?? null) ? ' — '.trim($data['commentaire']) : '');
        $abandon = $data['suite'] === 'abandon';

        $demande->update([
            'devis_refuse_le' => now(),
            'motif_refus_devis' => $motif,
            'devis_valide_le' => null,
            // A revised proposal is studied again; otherwise the demande ends.
            'statut' => $abandon ? 'REFUSEE' : 'EN_ETUDE',
        ]);

        $montant = number_format((float) $demande->montant_negocie, 0, ',', ' ');

        ActivityLog::record(null, 'devis refusé par le client', $demande, "{$demande->reference} : {$montant} FCFA, refusé par {$demande->client->nom} ({$motif})".($abandon ? ', sans suite.' : ', nouvelle proposition demandée.'));

        Notifier::alerterEquipe(
            Donnee::DemandesClients,
            Action::Modifier,
            'devis.refuse',
            "Devis refusé — {$demande->reference}",
            "{$demande->client->nom} a refusé le devis de {$montant} FCFA ({$demande->libelleObjet()}). Motif : {$motif}. "
                .($abandon ? 'Le client ne donne pas suite.' : 'Le client attend une nouvelle proposition.'),
            route('espace-admin.demandes.show', $demande),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $abandon
                ? 'Devis refusé : votre demande est close. Merci de votre retour.'
                : 'Devis refusé : l’équipe DAG vous fera une nouvelle proposition.',
        ]);

        return back();
    }

    /**
     * The devis of the demande as a proforma PDF.
     */
    public function proforma(Request $request, Demande $demande): HttpResponse
    {
        Gate::authorize('view', $demande);

        return ProformaPdf::telecharger($demande);
    }

    /**
     * The devis waits for the client: sent with an amount, not yet
     * validated, and its facture not paid yet.
     */
    private function peutValiderDevis(Demande $demande): bool
    {
        return $demande->statut === 'DEVIS_ENVOYE'
            && ProformaPdf::disponible($demande)
            && ! $demande->devisEstValide()
            && $demande->facture !== null
            && (float) $demande->facture->montant_paye <= 0;
    }
}
