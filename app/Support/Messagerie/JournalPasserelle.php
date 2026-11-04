<?php

namespace App\Support\Messagerie;

use Illuminate\Support\Facades\Log;

/**
 * Stand-in provider: writes the message to the application log instead of
 * sending it. Used until a real SMS / WhatsApp provider is connected.
 */
class JournalPasserelle implements PasserelleMessage
{
    public function envoyer(string $numero, string $message): void
    {
        Log::info("[Messagerie] {$numero} : {$message}");
    }
}
