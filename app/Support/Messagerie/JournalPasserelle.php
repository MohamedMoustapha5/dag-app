<?php

namespace App\Support\Messagerie;

use Illuminate\Support\Facades\Log;

/**
 * Stand-in provider: writes the message to the application log instead of
 * sending it. Used until a real SMS / WhatsApp provider is connected.
 */
class JournalPasserelle implements PasserelleMessage
{
    /**
     * The number is never written in full: only its last two digits, enough
     * to tell two recipients apart while testing.
     */
    public function envoyer(string $numero, string $message): void
    {
        $chiffres = preg_replace('/\D/', '', $numero) ?? '';

        Log::info('[Messagerie] numéro se terminant par '.substr($chiffres, -2).' : '.$message);
    }
}
