<?php

namespace App\Enums;

/**
 * Outcome of an online payment or payout as reported by the provider, whatever its
 * own status vocabulary.
 */
enum IssueTransaction: string
{
    case Reussi = 'REUSSI';
    case Echoue = 'ECHOUE';
    case EnAttente = 'EN_ATTENTE';
}
