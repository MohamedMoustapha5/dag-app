<?php

namespace App\Http\Controllers;

use App\Models\Paiement;
use App\Support\Paiements\PaiementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Notification sent by the payment provider when a payment is settled.
 * The body is JSON {"reference": "...", "statut": "success"|"failed"}
 * signed with HMAC SHA-256 in the X-Signature header; each provider's
 * own payload format is mapped to this one by its gateway adapter.
 */
class PaiementWebhookController extends Controller
{
    public function __invoke(Request $request, PaiementService $paiements): JsonResponse
    {
        $secret = config('paiements.webhook_secret');

        abort_if(empty($secret), 403, 'Webhook non configuré.');

        $attendue = hash_hmac('sha256', $request->getContent(), (string) $secret);

        abort_unless(hash_equals($attendue, (string) $request->header('X-Signature')), 403, 'Signature invalide.');

        $data = $request->validate([
            'reference' => ['required', 'string'],
            'statut' => ['required', 'in:success,failed'],
        ]);

        $paiement = Paiement::where('reference_transaction', $data['reference'])->first();

        abort_if($paiement === null, 404, 'Paiement inconnu.');

        if ($data['statut'] === 'success') {
            $paiements->confirmer($paiement);
        } else {
            $paiements->echouer($paiement);
        }

        return response()->json(['statut' => $paiement->fresh()->statut]);
    }
}
