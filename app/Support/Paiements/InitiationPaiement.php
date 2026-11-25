<?php

namespace App\Support\Paiements;

/**
 * What a provider answers when a payment is started: its transaction
 * reference, and the page the payer must be sent to when the provider
 * hosts the checkout (null when the payer confirms on their phone or on a
 * local screen).
 */
final readonly class InitiationPaiement
{
    public function __construct(
        public string $reference,
        public ?string $urlPaiement = null,
    ) {}
}
