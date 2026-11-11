<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Demande;
use App\Models\Facture;
use App\Models\Paiement;
use App\Support\Paiements\PaiementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminFactureController extends Controller
{
    use SharesAdminEspaceProps;

    public function index(Request $request): Response
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::FacturesPaiements, Action::Voir), 403);

        $factures = Facture::query()
            ->with(['client', 'entreprise', 'apprenant.user', 'demande.contrat', 'abonnement.formule', 'inscription.formation'])
            ->latest()
            ->get();

        $demandesAFacturer = Demande::query()
            ->whereNotIn('statut', ['BROUILLON', 'REFUSEE'])
            ->whereDoesntHave('facture')
            ->with(['client', 'entreprise', 'service', 'formule'])
            ->get();

        return Inertia::render('espaces/admin-factures', [
            ...$this->adminEspaceProps($membre),
            'peutCreer' => $membre->peut(Donnee::FacturesPaiements, Action::Creer),
            'peutGenererContrat' => $membre->peut(Donnee::ContratsClauses, Action::Creer),
            'factures' => $factures->map(fn (Facture $facture) => [
                'id' => $facture->id,
                'reference' => $facture->reference,
                'objet' => $facture->libelleObjet(),
                'client' => $this->nomPayeur($facture),
                'montant' => $facture->montant,
                'montantPaye' => $facture->montant_paye,
                'statut' => $facture->statut,
                'demandeId' => $facture->demande_id,
                'aContrat' => $facture->demande_id !== null && $facture->demande->contrat !== null,
            ]),
            'demandesAFacturer' => $demandesAFacturer->map(fn (Demande $demande) => [
                'id' => $demande->id,
                'reference' => $demande->reference,
                'objet' => $demande->libelleObjet(),
                'montantNegocie' => $demande->montant_negocie,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::FacturesPaiements, Action::Creer), 403);

        $data = $request->validate([
            'demande_id' => ['required', 'uuid', 'exists:demandes,id'],
            'montant' => ['required', 'numeric', 'min:0'],
        ]);

        $demande = Demande::where('id', $data['demande_id'])->firstOrFail();

        abort_if($demande->facture()->exists(), 409, 'Cette demande a déjà une facture.');

        $facture = Facture::create([
            'type' => 'SERVICE',
            'client_id' => $demande->client_id,
            'entreprise_id' => $demande->entreprise_id,
            'demande_id' => $demande->id,
            'montant' => $data['montant'],
            'montant_paye' => 0,
            'statut' => 'EN_ATTENTE',
        ]);

        ActivityLog::record($membre, 'a créé la facture', $facture, "{$facture->reference} : ".number_format((float) $facture->montant, 0, ',', ' ').' FCFA.');

        Inertia::flash('toast', ['type' => 'success', 'message' => "Facture {$facture->reference} créée."]);

        return to_route('espace-admin.factures.show', $facture);
    }

    public function show(Request $request, Facture $facture): Response
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::FacturesPaiements, Action::Voir), 403);

        $facture->load(['client', 'entreprise', 'demande', 'abonnement.formule', 'inscription.formation', 'paiements']);

        return Inertia::render('espaces/admin-facture-show', [
            ...$this->adminEspaceProps($membre),
            'peutCreer' => $membre->peut(Donnee::FacturesPaiements, Action::Creer),
            'peutModifier' => $membre->peut(Donnee::FacturesPaiements, Action::Modifier),
            'peutValider' => $membre->peut(Donnee::FacturesPaiements, Action::Valider),
            'peutGenererContrat' => $membre->peut(Donnee::ContratsClauses, Action::Creer),
            'facture' => [
                'id' => $facture->id,
                'reference' => $facture->reference,
                'objet' => $facture->libelleObjet(),
                'client' => $this->nomPayeur($facture),
                'montant' => $facture->montant,
                'montantPaye' => $facture->montant_paye,
                'statut' => $facture->statut,
                'demandeId' => $facture->demande_id,
                'aContrat' => $facture->demande_id !== null && $facture->demande->contrat()->exists(),
            ],
            'paiements' => $facture->paiements->map(fn (Paiement $paiement) => [
                'id' => $paiement->id,
                'montant' => $paiement->montant,
                'moyen' => $paiement->moyen,
                'referenceTransaction' => $paiement->reference_transaction,
                'statut' => $paiement->statut,
                'datePaiement' => $paiement->date_paiement?->toDateString(),
                'initieLe' => $paiement->created_at?->format('d/m/Y H:i'),
            ]),
        ]);
    }

    public function storePaiement(Request $request, Facture $facture, PaiementService $paiements): RedirectResponse
    {
        $membre = $this->membre($request);
        // A manual payment is confirmed as it is recorded, in the name of the
        // member: recording it is validating money received.
        abort_unless($membre->peut(Donnee::FacturesPaiements, Action::Creer) && $membre->peut(Donnee::FacturesPaiements, Action::Valider), 403);

        $data = $request->validate([
            'montant' => ['required', 'numeric', 'min:0.01'],
            'moyen' => ['required', Rule::in(['OM', 'MTN_MOMO', 'CARTE', 'ESPECES', 'VIREMENT', 'MANUEL'])],
            'reference_transaction' => ['nullable', 'string', 'max:191'],
        ]);

        $paiements->enregistrerManuel(
            $facture,
            (float) $data['montant'],
            $data['moyen'],
            $data['reference_transaction'] ?? null,
            $membre,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Paiement enregistré.']);

        return back();
    }

    /**
     * Settles an online payment left pending (the payer paid but the
     * provider's notification never arrived) or marks it failed.
     */
    public function updatePaiement(Request $request, Paiement $paiement, PaiementService $paiements): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::FacturesPaiements, Action::Modifier), 403);

        $data = $request->validate([
            'statut' => ['required', Rule::in(['CONFIRME', 'ECHEC'])],
        ]);

        abort_unless($paiement->statut === 'EN_ATTENTE', 409, 'Ce paiement a déjà été traité.');
        abort_if($data['statut'] === 'CONFIRME' && ! $membre->peut(Donnee::FacturesPaiements, Action::Valider), 403, 'Confirmer un paiement demande le droit de validation sur les paiements.');

        if ($data['statut'] === 'CONFIRME') {
            $paiements->confirmer($paiement, $membre);
        } else {
            $paiements->echouer($paiement);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Paiement mis à jour.']);

        return back();
    }

    private function nomPayeur(Facture $facture): string
    {
        if ($facture->client_id !== null) {
            return $facture->client->nom;
        }

        if ($facture->entreprise_id !== null) {
            return $facture->entreprise->nom;
        }

        if ($facture->apprenant_id !== null) {
            return $facture->apprenant->user->name;
        }

        return '—';
    }
}
