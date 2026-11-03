<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use App\Models\Facture;
use App\Models\Paiement;
use App\Support\Paiements\PaiementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Online payment of a facture by its payer (client, entreprise or
 * apprenant). Only the owner of the facture can pay it.
 */
class PaiementEnLigneController extends Controller
{
    public function __construct(private PaiementService $paiements) {}

    public function initier(Request $request, Facture $facture): RedirectResponse
    {
        abort_unless($facture->appartientA($request->user('web')), 404);

        $moyens = config('paiements.moyens');

        $data = $request->validate([
            'moyen' => ['required', Rule::in(array_keys($moyens))],
            'telephone' => ['nullable', 'string', 'max:30'],
        ]);

        $telephone = null;

        if ($moyens[$data['moyen']]['mobile']) {
            $telephone = $this->normaliserTelephone($data['telephone'] ?? '');

            if ($telephone === null) {
                throw ValidationException::withMessages([
                    'telephone' => 'Saisissez un numéro mobile camerounais valide (ex. 6 90 00 00 00).',
                ]);
            }
        }

        $paiement = $this->paiements->initier($facture, $data['moyen'], $telephone);

        if ($this->paiements->passerelle()->ecranLocal()) {
            return to_route('paiements.simulation', $paiement);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Demande de paiement envoyée : validez-la sur votre téléphone ou sur la page du prestataire.',
        ]);

        return back();
    }

    /**
     * Screen standing in for the provider's confirmation page while the
     * simulation gateway is active.
     */
    public function simulation(Request $request, Paiement $paiement): Response|RedirectResponse
    {
        $this->autoriserSimulation($request, $paiement);

        if ($paiement->statut !== 'EN_ATTENTE') {
            return redirect($this->retour($request));
        }

        return Inertia::render('public/paiement-simulation', [
            'paiement' => [
                'id' => $paiement->id,
                'montant' => (string) $paiement->montant,
                'moyen' => config("paiements.moyens.{$paiement->moyen}.label"),
                'reference' => $paiement->reference_transaction,
                'facture' => $paiement->facture->reference,
            ],
            'retour' => $this->retour($request),
        ]);
    }

    public function decision(Request $request, Paiement $paiement): RedirectResponse
    {
        $this->autoriserSimulation($request, $paiement);

        $data = $request->validate([
            'resultat' => ['required', Rule::in(['succes', 'echec'])],
        ]);

        if ($data['resultat'] === 'succes') {
            $this->paiements->confirmer($paiement);
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Paiement confirmé. Votre facture est réglée.']);
        } else {
            $this->paiements->echouer($paiement);
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Le paiement a échoué. Vous pouvez réessayer.']);
        }

        return redirect($this->retour($request));
    }

    private function autoriserSimulation(Request $request, Paiement $paiement): void
    {
        abort_unless($paiement->facture->appartientA($request->user('web')), 404);
        abort_unless($this->paiements->passerelle()->ecranLocal() && ! app()->isProduction(), 404);
    }

    private function retour(Request $request): string
    {
        return $request->user('web')->apprenant !== null
            ? route('espace-apprenant.factures')
            : route('espace-client.factures');
    }

    /**
     * Normalises a Cameroonian mobile number to 9 digits starting with 6,
     * or returns null when it is not one.
     */
    private function normaliserTelephone(string $saisie): ?string
    {
        $chiffres = preg_replace('/\D+/', '', $saisie) ?? '';

        if (str_starts_with($chiffres, '237') && strlen($chiffres) === 12) {
            $chiffres = substr($chiffres, 3);
        }

        return preg_match('/^6\d{8}$/', $chiffres) === 1 ? $chiffres : null;
    }
}
