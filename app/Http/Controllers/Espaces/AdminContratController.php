<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Clause;
use App\Models\Client;
use App\Models\Contrat;
use App\Models\Demande;
use App\Models\Formule;
use App\Models\MembreEquipe;
use App\Models\ModeleContrat;
use App\Models\Partenariat;
use App\Notifications\ContratASignerNotification;
use App\Notifications\ContratValideNotification;
use App\Support\ContratPdf;
use App\Support\ContratRedaction;
use App\Support\Notifications\Notifier;
use App\Support\Paiements\PaiementService;
use App\Support\Partenariats\GestionPartenariats;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminContratController extends Controller
{
    use SharesAdminEspaceProps;

    public function index(Request $request): Response
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::ContratsClauses, Action::Voir), 403);

        $contrats = Contrat::query()
            ->with(['client', 'apprenant.user', 'entreprise', 'demande.service', 'demande.formule', 'abonnement.formule', 'candidature.offre'])
            ->latest('updated_at')
            ->get();

        $demandesAContractualiser = Demande::query()
            ->whereNotIn('statut', ['BROUILLON', 'REFUSEE'])
            ->whereDoesntHave('contrat')
            ->with(['service', 'formule'])
            ->get();

        return Inertia::render('espaces/admin/contrats', [
            ...$this->adminEspaceProps($membre),
            'peutCreer' => $membre->peut(Donnee::ContratsClauses, Action::Creer),
            'peutModifier' => $membre->peut(Donnee::ContratsClauses, Action::Modifier),
            'peutSupprimer' => $membre->peut(Donnee::ContratsClauses, Action::Supprimer),
            'peutTelecharger' => $membre->peut(Donnee::ContratsClauses, Action::Telecharger),
            'modeles' => ModeleContrat::query()->orderBy('type')->orderBy('nom')->get(['id', 'nom', 'type', 'statut', 'contenu']),
            'clauses' => Clause::query()->orderBy('ordre')->orderBy('created_at')->get(['id', 'titre', 'contenu', 'ordre']),
            'variables' => ContratRedaction::variables(),
            'contrats' => $contrats->map(fn (Contrat $contrat) => [
                'id' => $contrat->id,
                'reference' => $contrat->reference,
                'objet' => $contrat->libelleObjet(),
                'partie' => $contrat->nomPartie(),
                'type' => $contrat->type,
                'statut' => $contrat->statut,
                'estSigne' => $contrat->estSigne(),
                'rectificationDemandee' => $contrat->statut === 'BROUILLON' && $contrat->motif_rectification !== null,
                'misAJourLe' => $contrat->updated_at?->format('d/m/Y'),
            ]),
            'demandesAContractualiser' => $demandesAContractualiser->map(fn (Demande $demande) => [
                'id' => $demande->id,
                'reference' => $demande->reference,
                'objet' => $demande->libelleObjet(),
            ]),
            'generation' => $membre->peut(Donnee::ContratsClauses, Action::Creer) ? [
                'structures' => Client::query()
                    ->whereIn('type', ['ENTREPRISE', 'ORGANISATION'])
                    ->orderBy('nom')
                    ->get()
                    ->map(fn (Client $client) => [
                        'id' => $client->id,
                        'nom' => $client->nom,
                        'typeLabel' => $client->typeLabel(),
                    ]),
                'formules' => Formule::query()->orderBy('nom')->get(['id', 'nom']),
                'domaines' => collect(Partenariat::DOMAINES)
                    ->map(fn (string $libelle, string $code) => ['code' => $code, 'libelle' => $libelle])
                    ->values(),
                'durees' => Partenariat::DUREES,
                'peutFacturer' => $membre->peut(Donnee::FacturesPaiements, Action::Creer),
                'peutPartenariat' => $membre->peut(Donnee::Partenariats, Action::Valider),
            ] : null,
        ]);
    }

    public function storeModele(Request $request): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::ContratsClauses, Action::Creer), 403);

        $data = $request->validate([
            'nom' => ['required', 'string', 'max:191'],
            'type' => ['required', Rule::in(['PRESTATION_SERVICE', 'ABONNEMENT', 'CONVENTION_STAGE', 'CONTRAT_PARTENAIRE'])],
            'contenu' => ['required', 'string'],
        ]);

        ModeleContrat::create([...$data, 'statut' => 'BROUILLON']);

        return back();
    }

    public function updateModele(Request $request, ModeleContrat $modeleContrat): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::ContratsClauses, Action::Modifier), 403);

        $data = $request->validate([
            'statut' => ['sometimes', 'required', Rule::in(['BROUILLON', 'ACTIF'])],
            'nom' => ['sometimes', 'required', 'string', 'max:191'],
            'contenu' => ['sometimes', 'required', 'string'],
        ]);

        // One active modèle per type: activating one makes it the modèle used
        // for the contracts drafted from now on, the others become drafts.
        DB::transaction(function () use ($modeleContrat, $data): void {
            if (($data['statut'] ?? null) === 'ACTIF') {
                ModeleContrat::query()
                    ->where('type', $modeleContrat->type)
                    ->whereKeyNot($modeleContrat->getKey())
                    ->where('statut', 'ACTIF')
                    ->update(['statut' => 'BROUILLON']);
            }

            $modeleContrat->update($data);
        });

        if (array_key_exists('contenu', $data) || array_key_exists('nom', $data)) {
            ActivityLog::record($membre, 'a modifié le modèle de contrat', $modeleContrat, $modeleContrat->nom.'.');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => match (true) {
            ($data['statut'] ?? null) === 'ACTIF' => "« {$modeleContrat->nom} » est le modèle utilisé pour les nouveaux contrats.",
            array_key_exists('contenu', $data) => 'Modèle enregistré. Il s\'applique aux contrats rédigés à partir de maintenant.',
            default => 'Modèle mis à jour.',
        }]);

        return back();
    }

    public function storeClause(Request $request): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::ContratsClauses, Action::Creer), 403);

        $data = $request->validate([
            'titre' => ['required', 'string', 'max:191'],
            'contenu' => ['required', 'string'],
            'ordre' => ['nullable', 'integer', 'min:0'],
        ]);

        // Without an explicit position, a new clause becomes the last article.
        Clause::create([...$data, 'ordre' => $data['ordre'] ?? ((int) Clause::max('ordre') + 1)]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Clause ajoutée à la fin du contrat.']);

        return back();
    }

    /**
     * Moves a clause one article up or down in the contract.
     */
    public function deplacerClause(Request $request, Clause $clause): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::ContratsClauses, Action::Modifier), 403);

        $data = $request->validate([
            'direction' => ['required', Rule::in(['haut', 'bas'])],
        ]);

        DB::transaction(function () use ($clause, $data): void {
            // Positions are renumbered 0..n-1 first, so equal or sparse values
            // from older clauses still move one step at a time.
            $clauses = Clause::query()->orderBy('ordre')->orderBy('created_at')->lockForUpdate()->get()->values();
            $position = $clauses->search(fn (Clause $item) => $item->is($clause));
            $cible = $data['direction'] === 'haut' ? $position - 1 : $position + 1;

            if ($position === false || $cible < 0 || $cible >= $clauses->count()) {
                return;
            }

            $ordre = $clauses->all();
            [$ordre[$position], $ordre[$cible]] = [$ordre[$cible], $ordre[$position]];

            foreach ($ordre as $index => $item) {
                if ($item->ordre !== $index) {
                    $item->update(['ordre' => $index]);
                }
            }
        });

        return back();
    }

    /**
     * Editing a clause changes the text of the contracts drafted from now on;
     * contracts already drafted keep the text they were generated with.
     */
    public function updateClause(Request $request, Clause $clause): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::ContratsClauses, Action::Modifier), 403);

        $data = $request->validate([
            'titre' => ['required', 'string', 'max:191'],
            'contenu' => ['required', 'string'],
            'ordre' => ['nullable', 'integer', 'min:0'],
        ]);

        $clause->update([...$data, 'ordre' => $data['ordre'] ?? $clause->ordre]);

        ActivityLog::record($membre, 'a modifié la clause', $clause, $clause->titre.'.');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Clause mise à jour.']);

        return back();
    }

    public function destroyClause(Request $request, Clause $clause): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::ContratsClauses, Action::Supprimer), 403);

        $clause->delete();

        return back();
    }

    public function store(Request $request): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::ContratsClauses, Action::Creer), 403);

        $data = $request->validate([
            'demande_id' => ['required', 'uuid', 'exists:demandes,id'],
            'modele_contrat_id' => ['nullable', 'uuid', 'exists:modeles_contrat,id'],
        ]);

        $demande = Demande::where('id', $data['demande_id'])->firstOrFail();

        abort_if($demande->contrat()->exists(), 409, 'Cette demande a déjà un contrat.');
        abort_unless($demande->exigeContrat(), 409, 'Cette demande ne passe pas par un contrat : le client valide son devis en ligne, puis paie.');

        $modele = isset($data['modele_contrat_id'])
            ? ModeleContrat::where('id', $data['modele_contrat_id'])->first()
            : null;

        // A demande gets a prestation or an abonnement contract: a modèle
        // written for a stage or a partnership would draft the wrong text.
        if ($modele !== null && $modele->type !== ContratRedaction::typePour($demande)) {
            throw ValidationException::withMessages([
                'modele_contrat_id' => 'Ce modèle est prévu pour un autre type de contrat : choisissez le modèle par défaut ou un modèle « '.(ContratRedaction::typePour($demande) === 'ABONNEMENT' ? 'Abonnement' : 'Prestation de service').' ».',
            ]);
        }

        $contrat = ContratRedaction::brouillonPour($demande, $modele);

        ActivityLog::record($this->membre($request), 'a généré le contrat', $contrat, "{$contrat->reference} pour la demande {$demande->reference}.");

        Inertia::flash('toast', ['type' => 'success', 'message' => "Contrat {$contrat->reference} créé."]);

        return to_route('espace-admin.contrats.show', $contrat);
    }

    /**
     * Generates a contract for an entreprise or an organisation directly,
     * for a deal agreed with DAG outside of a request.
     *
     * A prestation or a subscription follows the usual path of an order: a
     * request is recorded as agreed, with its facture, and the contract is
     * drafted from the active modèle; once signed and validated by DAG, the
     * client pays online. A partnership follows the partnership path, so
     * that its validation makes the structure an official partner.
     *
     * « Générer et envoyer » sends the contract for signature at once;
     * otherwise it stays a draft the team reviews before sending it.
     */
    public function generer(Request $request, PaiementService $paiements, GestionPartenariats $partenariats): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::ContratsClauses, Action::Creer), 403);

        $type = $request->validate([
            'type' => ['required', Rule::in(['PRESTATION_SERVICE', 'ABONNEMENT', 'CONTRAT_PARTENAIRE'])],
        ])['type'];

        if ($type === 'CONTRAT_PARTENAIRE') {
            abort_unless($membre->peut(Donnee::Partenariats, Action::Valider), 403, 'Proposer un partenariat demande le droit de validation sur les partenariats.');

            $data = $request->validate([
                'client_id' => ['required', 'uuid', Rule::exists('clients', 'id')],
                'duree_mois' => ['required', 'integer', Rule::in(Partenariat::DUREES)],
                'domaines' => ['required', 'array', 'min:1'],
                'domaines.*' => ['string', 'distinct', Rule::in(array_keys(Partenariat::DOMAINES))],
                'message' => ['nullable', 'string', 'max:2000'],
            ], [
                'client_id.required' => 'Choisissez l’entreprise à qui envoyer le contrat.',
                'domaines.required' => 'Choisissez au moins un domaine de collaboration.',
                'domaines.min' => 'Choisissez au moins un domaine de collaboration.',
            ]);

            $partenariat = $partenariats->proposer(Client::findOrFail($request->string('client_id')->value()), (int) $data['duree_mois'], $data['domaines'], $data['message'] ?? null, $membre);

            Inertia::flash('toast', ['type' => 'success', 'message' => "Contrat de partenariat {$partenariat->contrat->reference} envoyé à {$partenariat->client->nom} pour signature."]);

            return to_route('espace-admin.contrats.show', $partenariat->contrat);
        }

        // The contract comes with the facture the client pays once it is validated.
        abort_unless($membre->peut(Donnee::FacturesPaiements, Action::Creer), 403, 'Générer ce contrat émet sa facture : cela demande le droit de créer des factures.');

        $abonnement = $type === 'ABONNEMENT';

        $data = $request->validate([
            'client_id' => ['required', 'uuid', Rule::exists('clients', 'id')],
            'objet' => [$abonnement ? 'nullable' : 'required', 'string', 'max:191'],
            'formule_id' => [$abonnement ? 'required' : 'nullable', 'uuid', Rule::exists('formules', 'id')],
            'description' => ['required', 'string', 'min:10', 'max:3000'],
            'montant' => ['required', 'numeric', 'min:1', 'max:1000000000'],
            'modele_contrat_id' => ['nullable', 'uuid', Rule::exists('modeles_contrat', 'id')->where('type', $type)],
            'envoyer' => ['sometimes', 'boolean'],
        ], [
            'client_id.required' => 'Choisissez l’entreprise à qui envoyer le contrat.',
            'objet.required' => 'Indiquez l’objet de la prestation.',
            'formule_id.required' => 'Choisissez la formule de l’abonnement.',
            'description.required' => 'Décrivez ce qui est convenu avec le client : ce texte figure dans le contrat.',
            'description.min' => 'Décrivez ce qui est convenu en une phrase au moins.',
            'montant.required' => 'Indiquez le montant convenu.',
            'modele_contrat_id.exists' => 'Ce modèle est prévu pour un autre type de contrat.',
        ]);

        $client = Client::findOrFail($request->string('client_id')->value());

        // A particulier validates a devis online: the contract path is the entreprises'.
        if (! $client->estStructure()) {
            throw ValidationException::withMessages(['client_id' => 'Un contrat se génère pour une entreprise ou une organisation.']);
        }

        $modele = $request->filled('modele_contrat_id') ? ModeleContrat::find($request->string('modele_contrat_id')->value()) : null;
        $envoyer = $request->boolean('envoyer');

        $contrat = DB::transaction(function () use ($client, $data, $abonnement, $modele, $paiements): Contrat {
            $demande = $client->demandes()->create([
                'formule_id' => $abonnement ? $data['formule_id'] : null,
                'objet' => $abonnement ? null : $data['objet'],
                'description' => $data['description'],
                'montant_negocie' => $data['montant'],
                'statut' => 'DEVIS_ENVOYE',
                // Agreed with DAG beforehand: signing the contract is the client's acceptance.
                'devis_valide_le' => now(),
            ]);

            $paiements->emettreFacturePourDevis($demande, (float) $data['montant']);

            return ContratRedaction::brouillonPour($demande, $modele);
        });

        ActivityLog::record($membre, 'a généré le contrat', $contrat, "{$contrat->reference} pour {$client->nom} : {$contrat->libelleObjet()}.");

        if ($envoyer) {
            $this->changerStatut($contrat, 'ENVOYE', $membre);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $envoyer
            ? "Contrat {$contrat->reference} envoyé à {$client->nom} pour signature."
            : "Contrat {$contrat->reference} généré : relisez-le, puis envoyez-le à {$client->nom}."]);

        return to_route('espace-admin.contrats.show', $contrat);
    }

    public function show(Request $request, Contrat $contrat): Response
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::ContratsClauses, Action::Voir), 403);

        $contrat->load(['client', 'apprenant.user', 'entreprise', 'modeleContrat', 'demande', 'abonnement.formule', 'candidature.offre']);

        return Inertia::render('espaces/admin/contrat-show', [
            ...$this->adminEspaceProps($membre),
            'peutModifier' => $membre->peut(Donnee::ContratsClauses, Action::Modifier),
            'peutValider' => $membre->peut(Donnee::ContratsClauses, Action::Valider),
            'peutTelecharger' => $membre->peut(Donnee::ContratsClauses, Action::Telecharger),
            'contrat' => [
                'id' => $contrat->id,
                'reference' => $contrat->reference,
                'objet' => $contrat->libelleObjet(),
                'partie' => $contrat->nomPartie(),
                'demandeId' => $contrat->demande_id,
                'type' => $contrat->type,
                'statut' => $contrat->statut,
                'statutsAccessibles' => $contrat->statutsAccessibles(),
                'montant' => $contrat->montant,
                'contenu' => $contrat->contenu,
                'codeVerification' => $contrat->code_verification,
                'signataireNom' => $contrat->signataire_nom,
                'dateSignature' => $contrat->date_signature?->format('d/m/Y à H:i'),
                'signatureContestee' => $contrat->signature_contestee,
                'estSigne' => $contrat->estSigne(),
                'identite' => $contrat->cni_numero === null && $contrat->telephone_cocontractant === null ? null : [
                    'telephone' => $contrat->telephone_cocontractant,
                    'cniNumero' => $contrat->cni_numero,
                    'cniLieuDelivrance' => $contrat->cni_lieu_delivrance,
                    'cniDateDelivrance' => $contrat->cni_date_delivrance?->format('d/m/Y'),
                    'villeResidence' => $contrat->ville_residence,
                    'quartierPrestation' => $contrat->quartier_prestation,
                ],
                'rectification' => [
                    'nombre' => (int) $contrat->nombre_rectifications,
                    'max' => Contrat::RECTIFICATIONS_MAX,
                    'motif' => $contrat->motif_rectification,
                    'demandeeLe' => $contrat->rectification_demandee_at?->format('d/m/Y à H:i'),
                ],
            ],
        ]);
    }

    /**
     * Corrects the text of a contract still in draft, typically after the
     * client asked for a rectification; it is then sent again for signature.
     * A sent or signed contract is never edited.
     */
    public function updateContenu(Request $request, Contrat $contrat): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::ContratsClauses, Action::Modifier), 403);
        abort_unless($contrat->statut === 'BROUILLON', 409, 'Seul un contrat en brouillon peut être modifié : remettez-le en brouillon avant de le corriger.');

        $data = $request->validate([
            'contenu' => ['required', 'string', 'max:60000'],
            'envoyer' => ['sometimes', 'boolean'],
        ], [
            'contenu.required' => 'Le texte du contrat ne peut pas être vide.',
        ]);

        if ($contrat->contenu !== $data['contenu']) {
            $contrat->update(['contenu' => $data['contenu']]);

            ActivityLog::record($membre, 'a modifié le texte du contrat', $contrat, "{$contrat->reference}.");
        }

        // "Enregistrer et envoyer": one click saves the text and sends it for signature.
        if ($request->boolean('envoyer')) {
            $this->changerStatut($contrat, 'ENVOYE', $membre);

            Inertia::flash('toast', ['type' => 'success', 'message' => 'Contrat envoyé au client pour signature.']);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Texte du contrat enregistré.']);

        return back();
    }

    public function pdf(Request $request, Contrat $contrat): HttpResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::ContratsClauses, Action::Telecharger), 403);
        abort_unless($contrat->estSigne(), 409, "Ce contrat n'a pas encore été signé.");

        return ContratPdf::telecharger($contrat);
    }

    public function update(Request $request, Contrat $contrat): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::ContratsClauses, Action::Modifier), 403);

        $data = $request->validate([
            'statut' => ['required', Rule::in(array_keys(Contrat::STATUT_LABELS))],
        ]);

        // Validating a signed contract opens the payment to the client: it is
        // a validation, not an edit, whatever level the role was given.
        abort_if($data['statut'] === 'ACTIF' && ! $membre->peut(Donnee::ContratsClauses, Action::Valider), 403, 'Valider un contrat demande le droit de validation sur les contrats.');

        if (! $contrat->peutPasserA($data['statut'])) {
            throw ValidationException::withMessages([
                'statut' => $data['statut'] === 'SIGNE'
                    ? 'Seule la signature du client fait passer un contrat à « Signé ».'
                    : "Un contrat « {$contrat->libelleStatut()} » ne peut pas passer à « ".Contrat::STATUT_LABELS[$data['statut']].' ».',
            ]);
        }

        $this->changerStatut($contrat, $data['statut'], $membre);

        Inertia::flash('toast', ['type' => 'success', 'message' => match ($data['statut']) {
            'ENVOYE' => 'Contrat envoyé au client pour signature.',
            'BROUILLON' => 'Contrat repassé en brouillon : vous pouvez le corriger.',
            'ACTIF' => match (true) {
                $contrat->demande_id !== null => 'Contrat validé : le client est prévenu et peut régler sa facture.',
                $contrat->type === 'CONTRAT_PARTENAIRE' => "Partenariat validé : {$contrat->nomPartie()} est Partenaire officiel de Digital Agency Group.",
                default => 'Contrat validé : il est maintenant actif.',
            },
            'TERMINE' => 'Contrat marqué comme terminé.',
            'RESILIE' => 'Contrat résilié.',
            'REFUSE' => 'Contrat marqué comme refusé.',
            default => 'Contrat mis à jour.',
        }]);

        return back();
    }

    /**
     * Applies an allowed status change (checked by the caller) and tells the
     * client when the contract is sent to them for signature.
     */
    private function changerStatut(Contrat $contrat, string $statut, MembreEquipe $membre): void
    {
        abort_unless($contrat->peutPasserA($statut), 409, "Un contrat « {$contrat->libelleStatut()} » ne peut pas passer à « ".Contrat::STATUT_LABELS[$statut].' ».');

        $contrat->update(['statut' => $statut]);

        if ($statut === 'ENVOYE') {
            Notifier::envoyer(Notifier::utilisateurDe($contrat), new ContratASignerNotification($contrat));
        }

        // Validating a demande's signed contract opens the payment of its facture.
        if ($statut === 'ACTIF' && $contrat->demande_id !== null) {
            Notifier::envoyer(Notifier::utilisateurDe($contrat), new ContratValideNotification($contrat));
        }

        // A partnership contract opens or closes its partnership.
        if ($contrat->type === 'CONTRAT_PARTENAIRE') {
            app(GestionPartenariats::class)->contratMisAJour($contrat);
        }

        ActivityLog::record($membre, 'a changé le statut du contrat', $contrat, "{$contrat->reference} : {$contrat->libelleStatut()}.");
    }
}
