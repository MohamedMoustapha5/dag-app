<?php

namespace App\Support\Messagerie;

/**
 * A provider able to send a short text message (SMS or WhatsApp) to a phone
 * number. Real providers implement this interface and are declared in
 * config/notifications.php.
 */
interface PasserelleMessage
{
    public function envoyer(string $numero, string $message): void;
}
