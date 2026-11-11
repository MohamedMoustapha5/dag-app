<?php

namespace App\Support\Paiements;

use App\Models\Paiement;

/**
 * A payment opened by PaiementService::initier(), with the provider page
 * the payer must be redirected to, if any.
 */
final readonly class PaiementInitie
{
    public function __construct(
        public Paiement $paiement,
        public ?string $urlPaiement = null,
    ) {}
}
