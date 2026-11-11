<?php

namespace App\Http\Controllers;

use App\Models\Decaissement;
use App\Models\Paiement;
use App\Support\Paiements\DecaissementService;
use App\Support\Paiements\PaiementService;
use App\Support\Paiements\PasserelleDecaissement;
use App\Support\Paiements\PasserelleIndisponible;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Notification sent by the payment provider when a payment or a retrait
 * is settled. The active gateway authenticates it and turns its own
 * payload format into a ResultatTransaction (lireNotification() /
 * lireNotificationDecaissement()), so this endpoint does not change with
 * the provider.
 */
class PaiementWebhookController extends Controller
{
    public function __invoke(Request $request, PaiementService $paiements, DecaissementService $decaissements): JsonResponse
    {
        $passerelle = $paiements->passerelle();

        try {
            if ($passerelle instanceof PasserelleDecaissement && ($retrait = $passerelle->lireNotificationDecaissement($request)) !== null) {
                $decaissement = Decaissement::where('reference_transaction', $retrait->reference)->first();

                abort_if($decaissement === null, 404, 'Retrait inconnu.');

                $decaissements->appliquerResultat($decaissement, $retrait);

                return response()->json(['statut' => $decaissement->fresh()->statut]);
            }

            $resultat = $passerelle->lireNotification($request);
        } catch (PasserelleIndisponible $erreur) {
            // Not acknowledged: the provider retries the notification later.
            abort(503, $erreur->getMessage());
        }

        if ($resultat === null) {
            return response()->json(['statut' => 'ignore']);
        }

        $paiement = Paiement::where('reference_transaction', $resultat->reference)->first();

        abort_if($paiement === null, 404, 'Paiement inconnu.');
        abort_unless($paiements->appliquerResultat($paiement, $resultat), 422, 'Montant différent du paiement attendu.');

        return response()->json(['statut' => $paiement->fresh()->statut]);
    }
}
