<?php

namespace App\Support\Paiements;

use Illuminate\Http\Request;

/**
 * HMAC SHA-256 of the raw request body with the webhook secret shared with
 * the provider (paiements.webhook_secret). Without a secret every
 * notification is refused.
 */
class WebhookSignature
{
    public static function verifier(Request $request, string $entete): void
    {
        $secret = (string) config('paiements.webhook_secret');

        abort_if($secret === '', 403, 'Webhook non configuré.');

        $attendue = hash_hmac('sha256', $request->getContent(), $secret);

        abort_unless(hash_equals($attendue, (string) $request->header($entete)), 403, 'Signature invalide.');
    }
}
