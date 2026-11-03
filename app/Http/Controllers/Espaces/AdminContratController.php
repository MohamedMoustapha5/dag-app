<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Clause;
use App\Models\Contrat;
use App\Models\Demande;
use App\Models\ModeleContrat;
use App\Support\ContratPdf;
use App\Support\ContratRedaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Validation\Rule;
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
            ->with(['client', 'entreprise', 'demande', 'abonnement.formule', 'candidature.offre'])
            ->latest()
            ->get();

        // Only demandes the client has paid for can be contracted.
        $demandesAContractualiser = Demande::query()
            ->whereIn('statut', ['PAYEE', 'EN_COURS', 'LIVREE', 'TERMINEE'])
            ->whereDoesntHave('contrat')
            ->with(['client.user', 'entreprise', 'service', 'formule', 'facture.paiements'])
            ->latest('updated_at')
            ->get();

        return Inertia::render('espaces/admin-contrats', [
            ...$this->adminEspaceProps($membre),
            'peutCreer' => $membre->peut(Donnee::ContratsClauses, Action::Creer),
            'peutModifier' => $membre->peut(Donnee::ContratsClauses, Action::Modifier),
            'peutSupprimer' => $membre->peut(Donnee::ContratsClauses, Action::Supprimer),
            'peutTelecharger' => $membre->peut(Donnee::ContratsClauses, Action::Telecharger),
            'modeles' => ModeleContrat::query()->orderBy('nom')->get(['id', 'nom', 'type', 'statut']),
            'clauses' => Clause::query()->orderBy('ordre')->get(['id', 'titre', 'ordre']),
            'contrats' => $contrats->map(fn (Contrat $contrat) => [
                'id' => $contrat->id,
                'reference' => $contrat->reference,
                'objet' => $contrat->libelleObjet(),
                'type' => $contrat->type,
                'statut' => $contrat->statut,
                'estSigne' => $contrat->estSigne(),
            ]),
            'demandesAContractualiser' => $demandesAContractualiser->map(fn (Demande $demande) => [
                'id' => $demande->id,
                'reference' => $demande->reference,
                'objet' => $demande->libelleObjet(),
                'client' => $this->coordonneesClient($demande),
                'montant' => $demande->montant_negocie,
                'payeLe' => $this->datePaiement($demande),
            ]),
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
            'statut' => ['required', Rule::in(['BROUILLON', 'ACTIF'])],
        ]);

        $modeleContrat->update($data);

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

        Clause::create([...$data, 'ordre' => $data['ordre'] ?? 0]);

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

        $modele = isset($data['modele_contrat_id'])
            ? ModeleContrat::where('id', $data['modele_contrat_id'])->first()
            : ContratRedaction::modeleParDefaut('PRESTATION_SERVICE');

        $contrat = Contrat::create([
            'type' => 'PRESTATION_SERVICE',
            'modele_contrat_id' => $modele?->id,
            'client_id' => $demande->client_id,
            'entreprise_id' => $demande->entreprise_id,
            'demande_id' => $demande->id,
            'contenu' => ContratRedaction::pour($demande, $modele),
            'montant' => $demande->montant_negocie,
            'statut' => 'BROUILLON',
        ]);

        ActivityLog::record($this->membre($request), 'a généré le contrat', $contrat, "{$contrat->reference} pour la demande {$demande->reference}.");

        Inertia::flash('toast', ['type' => 'success', 'message' => "Contrat {$contrat->reference} créé."]);

        return to_route('espace-admin.contrats.show', $contrat);
    }

    public function show(Request $request, Contrat $contrat): Response
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::ContratsClauses, Action::Voir), 403);

        $contrat->load(['client', 'entreprise', 'modeleContrat', 'demande', 'abonnement.formule', 'candidature.offre']);

        return Inertia::render('espaces/admin-contrat-show', [
            ...$this->adminEspaceProps($membre),
            'peutModifier' => $membre->peut(Donnee::ContratsClauses, Action::Modifier),
            'peutTelecharger' => $membre->peut(Donnee::ContratsClauses, Action::Telecharger),
            'contrat' => [
                'id' => $contrat->id,
                'reference' => $contrat->reference,
                'objet' => $contrat->libelleObjet(),
                'type' => $contrat->type,
                'statut' => $contrat->statut,
                'montant' => $contrat->montant,
                'contenu' => $contrat->contenu,
                'codeVerification' => $contrat->code_verification,
                'signataireNom' => $contrat->signataire_nom,
                'dateSignature' => $contrat->date_signature?->toDateString(),
                'signatureContestee' => $contrat->signature_contestee,
                'estSigne' => $contrat->estSigne(),
            ],
        ]);
    }

    /**
     * Who paid, so DAG knows who the contrat is for.
     *
     * @return array{nom: string, email: string|null, telephone: string|null}
     */
    private function coordonneesClient(Demande $demande): array
    {
        if ($demande->client_id !== null) {
            return [
                'nom' => $demande->client->nom,
                'email' => $demande->client->user->email,
                'telephone' => $demande->client->telephone,
            ];
        }

        if ($demande->entreprise_id !== null) {
            return [
                'nom' => $demande->entreprise->nom,
                'email' => $demande->entreprise->email,
                'telephone' => $demande->entreprise->telephone,
            ];
        }

        return ['nom' => '—', 'email' => null, 'telephone' => null];
    }

    private function datePaiement(Demande $demande): ?string
    {
        $facture = $demande->facture;

        if ($facture === null) {
            return null;
        }

        $dernier = $facture->paiements->whereNotNull('date_paiement')->sortByDesc('date_paiement')->first();

        return ($dernier !== null && $dernier->date_paiement !== null
            ? $dernier->date_paiement
            : $facture->updated_at)?->format('d/m/Y');
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
            'statut' => ['required', Rule::in(['BROUILLON', 'ENVOYE', 'SIGNE', 'ACTIF', 'TERMINE', 'REFUSE', 'RESILIE'])],
        ]);

        $contrat->update($data);

        ActivityLog::record($this->membre($request), 'a changé le statut du contrat', $contrat, "{$contrat->reference} : {$contrat->libelleStatut()}.");

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Contrat mis à jour.']);

        return back();
    }
}
