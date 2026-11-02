<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\Demande;
use App\Models\Facture;
use App\Models\Paiement;
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
            ->with(['client', 'entreprise', 'apprenant.user', 'demande', 'abonnement.formule', 'inscription.formation'])
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
            'factures' => $factures->map(fn (Facture $facture) => [
                'id' => $facture->id,
                'reference' => $facture->reference,
                'objet' => $facture->libelleObjet(),
                'client' => $this->nomPayeur($facture),
                'montant' => $facture->montant,
                'montantPaye' => $facture->montant_paye,
                'statut' => $facture->statut,
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
            'facture' => [
                'id' => $facture->id,
                'reference' => $facture->reference,
                'objet' => $facture->libelleObjet(),
                'client' => $this->nomPayeur($facture),
                'montant' => $facture->montant,
                'montantPaye' => $facture->montant_paye,
                'statut' => $facture->statut,
            ],
            'paiements' => $facture->paiements->map(fn (Paiement $paiement) => [
                'id' => $paiement->id,
                'montant' => $paiement->montant,
                'moyen' => $paiement->moyen,
                'referenceTransaction' => $paiement->reference_transaction,
                'statut' => $paiement->statut,
                'datePaiement' => $paiement->date_paiement?->toDateString(),
            ]),
        ]);
    }

    public function storePaiement(Request $request, Facture $facture): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::FacturesPaiements, Action::Creer), 403);

        $data = $request->validate([
            'montant' => ['required', 'numeric', 'min:0.01'],
            'moyen' => ['required', Rule::in(['OM', 'MTN_MOMO', 'CARTE', 'ESPECES', 'VIREMENT', 'MANUEL'])],
            'reference_transaction' => ['nullable', 'string', 'max:191'],
        ]);

        Paiement::create([
            ...$data,
            'facture_id' => $facture->id,
            'statut' => 'CONFIRME',
            'valide_par_id' => $membre->id,
            'date_paiement' => now(),
        ]);

        $montantPaye = (float) $facture->montant_paye + (float) $data['montant'];
        $soldee = $montantPaye >= (float) $facture->montant;

        $facture->update([
            'montant_paye' => $montantPaye,
            'statut' => $soldee ? 'PAYEE' : 'PARTIELLEMENT_PAYEE',
        ]);

        if ($soldee && $facture->demande_id !== null) {
            $demande = $facture->demande;
            $etapeActuelle = array_search($demande->statut, Demande::SUIVI_STEPS, true);
            $etapePayee = array_search('PAYEE', Demande::SUIVI_STEPS, true);

            if ($etapeActuelle !== false && $etapeActuelle < $etapePayee) {
                $demande->update(['statut' => 'PAYEE']);
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Paiement enregistré.']);

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
