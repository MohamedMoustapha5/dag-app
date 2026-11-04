<?php

namespace App\Notifications\Canaux;

use App\Notifications\NotificationDag;

class WhatsAppChannel extends CanalMessage
{
    protected function cle(): string
    {
        return 'whatsapp';
    }

    protected function texte(NotificationDag $notification, object $notifiable): ?string
    {
        return $notification->toWhatsApp($notifiable);
    }
}
