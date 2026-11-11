<?php

namespace App\Support\Paiements;

use App\Enums\IssueTransaction;

/**
 * A provider's statement about one of its transactions (payment or
 * payout), normalised so the rest of the application never depends on a
 * provider's payload format. The amount and currency are the ones actually
 * moved, when known; the failure reason is the provider's message, when it
 * gives one.
 */
final readonly class ResultatTransaction
{
    public function __construct(
        public string $reference,
        public IssueTransaction $issue,
        public ?float $montant = null,
        public ?string $devise = null,
        public ?string $motifEchec = null,
    ) {}
}
