<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Demande;
use App\Notifications\DevisRecuNotification;
use App\Support\ContratRedaction;
use App\Support\Notifications\Notifier;
use App\Support\Paiements\PaiementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminDemandeController extends Controller
{
    use SharesAdminEspaceProps;

    public function index(Request $request): Response
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::DemandesClients, Action::Voir), 403);

        $demandes = Demande::query()
            ->with(['client', 'entreprise', 'service', 'formule', 'facture', 'contrat'])
            ->withExists('contrat')
            ->latest('updated_at')
            ->get();

        return Inertia::render('espaces/admin-demandes', [
            ...$this->adminEspaceProps($membre),
            'peutModifier' => $membre->peut(Donnee::DemandesClients, Action::Modifier),
            'peutEnvoyerDevis' => $membre->peut(Donnee::DemandesClients, Action::Modifier)
                && $membre->peut(Donnee::FacturesPaiements, Action::Creer),
            'peutGenererContrat' => $membre->peut(Donnee::ContratsClauses, Action::Creer),
            'peutEnvoyerContrat' => $membre->peut(Donnee::ContratsClauses, Action::Modifier),
            'demandes' => $demandes->map(fn (Demande $demande) => [
                'id' => $demande->id,
                'reference' => $demande->reference,
                'client' => $this->nomClient($demande),
                'objet' => $demande->libelleObjet(),
                'statut' => $demande->statut,
                'montantNegocie' => $demande->montant_negocie,
                'aContrat' => $demande->contrat_exists,
                'contratStatut' => $demande->contrat !== null ? $demande->contrat->statut : null,
                'contratId' => $demande->contrat !== null ? $demande->contrat->id : null,
                'payee' => $demande->facture !== null && $demande->facture->statut === 'PAYEE',
                'updatedAt' => $demande->updated_at?->toDateString(),
            ]),
        ]);
    }

    public function show(Request $request, Demande $demande): Response
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::DemandesClients, Action::Voir), 403);

        $demande->load(['client', 'entreprise', 'service', 'formule', 'contrat', 'facture']);

        $etapeActuelle = array_search($demande->statut, Demande::SUIVI_STEPS, true);

        return Inertia::render('espaces/admin-demande-show', [
            ...$this->adminEspaceProps($membre),
            'peutModifier' => $membre->peut(Donnee::DemandesClients, Action::Modifier),
            'peutGenererContrat' => $membre->peut(Donnee::ContratsClauses, Action::Creer),
            'demande' => [
                'id' => $demande->id,
                'reference' => $demande->reference,
                'client' => $this->nomClient($demande),
                'clientTelephone' => $demande->client_id !== null ? $demande->client->telephone : null,
                'objet' => $demande->libelleObjet(),
                'description' => $demande->description,
                'statut' => $demande->statut,
                'montantNegocie' => $demande->montant_negocie,
                'payee' => $demande->facture !== null && $demande->facture->statut === 'PAYEE',
                'createdAt' => $demande->created_at?->toDateString(),
                'contrat' => $demande->contrat !== null ? [
                    'reference' => $demande->contrat->reference,
                    'statut' => $demande->contrat->statut,
                ] : null,
                'facture' => $demande->facture !== null ? [
                    'reference' => $demande->facture->reference,
                    'statut' => $demande->facture->statut,
                ] : null,
            ],
            'suivi' => [
                'etapes' => Demande::SUIVI_STEPS,
                'etapeActuelle' => $etapeActuelle === false ? null : $etapeActuelle,
                'refusee' => $demande->statut === 'REFUSEE',
            ],
        ]);
    }

    public function update(Request $request, Demande $demande, PaiementService $paiements): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::DemandesClients, Action::Modifier), 403);

        $data = $request->validate([
            'statut' => ['required', Rule::in([...Demande::SUIVI_STEPS, 'REFUSEE'])],
            'montant_negocie' => ['nullable', 'numeric', 'min:0'],
        ]);

        // "Payée" reflects money actually received: only a settled facture
        // (PaiementService) moves a demande there, never a manual change.
        if ($data['statut'] === 'PAYEE' && $demande->statut !== 'PAYEE' && $demande->facture?->statut !== 'PAYEE') {
            throw ValidationException::withMessages([
                'statut' => 'Une demande passe à « Payée » quand sa facture est réglée, pas à la main.',
            ]);
        }

        // Issuing or re-pricing the facture is a finance action, gated by the
        // factures right on top of the demandes one.
        if ($data['statut'] === 'DEVIS_ENVOYE' && (float) ($data['montant_negocie'] ?? $demande->montant_negocie) > 0
            && ! $membre->peut(Donnee::FacturesPaiements, Action::Creer)) {
            throw ValidationException::withMessages([
                'statut' => 'Fixer le montant facturé demande le droit de créer des factures.',
            ]);
        }

        // The contract is mandatory and signed before payment: only the client's
        // signature moves a demande to "Contrat signé", and no prestation starts,
        // is delivered or closed without it.
        if (in_array($data['statut'], ['CONTRAT_SIGNE', 'EN_COURS', 'LIVREE', 'TERMINEE'], true) && ! $demande->contratEstSigne()) {
            throw ValidationException::withMessages([
                'statut' => $data['statut'] === 'CONTRAT_SIGNE'
                    ? 'Une demande passe à « Contrat signé » quand le client signe son contrat, pas à la main.'
                    : 'Le contrat doit être signé par le client avant de démarrer la prestation.',
            ]);
        }

        // The signed contract carries the amount the client agreed to.
        if ($data['statut'] === 'DEVIS_ENVOYE' && $demande->contratEstSigne()) {
            throw ValidationException::withMessages([
                'statut' => 'Le client a déjà signé le contrat : le devis ne peut plus être modifié.',
            ]);
        }

        DB::transaction(function () use ($demande, $data, $paiements): void {
            $demande->update($data);

            // Moving a demande to "Devis envoyé" with an amount issues the
            // facture and sends the contract the client signs before paying,
            // whichever screen the change comes from.
            if ($demande->statut === 'DEVIS_ENVOYE' && (float) $demande->montant_negocie > 0) {
                $paiements->emettreFacturePourDevis($demande, (float) $demande->montant_negocie);
                ContratRedaction::envoyerAvecDevis($demande);
            }
        });

        ActivityLog::record($this->membre($request), 'a mis à jour la demande', $demande, "{$demande->reference} : {$demande->libelleStatut()}.");

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Demande mise à jour.']);

        return back();
    }

    public function envoyerDevis(Request $request, Demande $demande, PaiementService $paiements): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::DemandesClients, Action::Modifier), 403);
        // Sending the devis issues (or re-prices) the facture the client pays.
        abort_unless($membre->peut(Donnee::FacturesPaiements, Action::Creer), 403, 'Envoyer un devis demande le droit de créer des factures.');
        abort_unless(
            in_array($demande->statut, ['BROUILLON', 'EN_ETUDE', 'DEVIS_ENVOYE'], true),
            409,
            'Un devis ne peut plus être envoyé pour cette demande.'
        );

        $data = $request->validate([
            'montant' => ['required', 'numeric', 'min:1'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $facture = $demande->facture;

        abort_if(
            $facture !== null && (float) $facture->montant_paye > 0,
            409,
            'Un paiement a déjà été enregistré : le devis ne peut plus être modifié.'
        );

        abort_if(
            $demande->contratEstSigne(),
            409,
            'Le client a déjà signé le contrat : le devis ne peut plus être modifié.'
        );

        // The devis comes with its contract: the client reads and signs it
        // first, then pays the facture.
        DB::transaction(function () use ($demande, $data, $paiements): void {
            $demande->update([
                'montant_negocie' => $data['montant'],
                'message_devis' => $data['message'] ?? null,
                'statut' => 'DEVIS_ENVOYE',
            ]);

            $paiements->emettreFacturePourDevis($demande, (float) $data['montant']);
            ContratRedaction::envoyerAvecDevis($demande);
        });

        Notifier::envoyer(Notifier::utilisateurDe($demande), new DevisRecuNotification($demande));

        ActivityLog::record($this->membre($request), 'a envoyé un devis', $demande, "{$demande->reference} : ".number_format((float) $data['montant'], 0, ',', ' ').' FCFA.');

        Inertia::flash('toast', ['type' => 'success', 'message' => "Devis envoyé pour {$demande->reference}."]);

        return back();
    }

    /**
     * Mirrors Demande::libelleObjet()'s convention of branching on the FK
     * column rather than nullsafe-chaining the relation: Larastan can't
     * see that client_id/entreprise_id are nullable from the BelongsTo
     * return type alone, so it would otherwise (wrongly) flag `?->` here
     * as unnecessary — removing it would be a real null-pointer risk,
     * since exactly one of the two is actually set per demande.
     */
    private function nomClient(Demande $demande): string
    {
        if ($demande->client_id !== null) {
            return $demande->client->nom;
        }

        if ($demande->entreprise_id !== null) {
            return $demande->entreprise->nom;
        }

        return '—';
    }
}
