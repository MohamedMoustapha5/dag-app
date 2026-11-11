<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use App\Models\Facture;
use App\Models\Paiement;
use App\Support\Paiements\PaiementService;
use App\Support\Paiements\PasserelleIndisponible;
use App\Support\Paiements\SimulationPasserelle;
use App\Support\Paiements\TelephoneMobile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Online payment of a facture by its payer (client, entreprise or
 * apprenant). Only the owner of the facture can pay it.
 */
class PaiementEnLigneController extends Controller
{
    public function __construct(private PaiementService $paiements) {}

    public function initier(Request $request, Facture $facture): SymfonyResponse
    {
        abort_unless($facture->appartientA($request->user('web')), 404);

        $moyens = config('paiements.moyens');

        $data = $request->validate([
            'moyen' => ['required', Rule::in(array_keys($moyens))],
            'telephone' => ['nullable', 'string', 'max:30'],
        ]);

        $telephone = null;

        if ($moyens[$data['moyen']]['mobile']) {
            $telephone = TelephoneMobile::normaliser($data['telephone'] ?? '');

            if ($telephone === null) {
                throw ValidationException::withMessages([
                    'telephone' => 'Saisissez un numéro mobile camerounais valide (ex. 6 90 00 00 00).',
                ]);
            }
        }

        try {
            $initie = $this->paiements->initier($facture, $data['moyen'], $telephone, $request->user('web'));
        } catch (PasserelleIndisponible $erreur) {
            throw ValidationException::withMessages(['moyen' => $erreur->getMessage()]);
        }

        if ($this->paiements->passerelle()->ecranLocal()) {
            return to_route('paiements.simulation', $initie->paiement);
        }

        if ($initie->urlPaiement !== null) {
            return Inertia::location($initie->urlPaiement);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Demande de paiement envoyée : validez-la sur votre téléphone ou sur la page du prestataire.',
        ]);

        return back();
    }

    /**
     * Where the provider sends the payer back after its checkout page. The
     * query string it adds is never trusted: the outcome is asked to the
     * provider, and the webhook stays the main confirmation path.
     */
    public function retourPrestataire(Request $request, Paiement $paiement): RedirectResponse
    {
        abort_unless($paiement->facture->appartientA($request->user('web')), 404);

        if ($paiement->statut === 'EN_ATTENTE' && $paiement->reference_transaction !== null) {
            try {
                $resultat = $this->paiements->passerelle()->verifier($paiement->reference_transaction);
                $conforme = $this->paiements->appliquerResultat($paiement, $resultat);
            } catch (PasserelleIndisponible) {
                $conforme = true;
            }

            if (! $conforme) {
                Inertia::flash('toast', ['type' => 'error', 'message' => 'Le montant reçu ne correspond pas à la facture : l\'équipe DAG va vérifier votre paiement.']);

                return redirect($this->retour($request));
            }
        }

        Inertia::flash('toast', match ($paiement->fresh()->statut) {
            'CONFIRME' => ['type' => 'success', 'message' => 'Paiement confirmé. Votre facture est réglée.'],
            'ECHEC' => ['type' => 'error', 'message' => 'Le paiement a échoué ou a été annulé. Vous pouvez réessayer.'],
            default => ['type' => 'success', 'message' => 'Paiement en cours de confirmation : votre facture sera mise à jour dès sa validation.'],
        });

        return redirect($this->retour($request));
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
        abort_unless($this->paiements->passerelle()->ecranLocal() && SimulationPasserelle::autorisee(), 404);
    }

    private function retour(Request $request): string
    {
        return $request->user('web')->apprenant !== null
            ? route('espace-apprenant.factures')
            : route('espace-client.factures');
    }
}
