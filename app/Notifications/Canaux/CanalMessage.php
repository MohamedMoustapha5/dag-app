<?php

namespace App\Notifications\Canaux;

use App\Notifications\NotificationDag;
use App\Support\Messagerie\PasserelleMessage;
use Illuminate\Notifications\Notification;

/**
 * Shared behaviour of the short-message channels: the notification supplies the
 * text (toSms / toWhatsApp), the provider configured for the channel sends it.
 */
abstract class CanalMessage
{
    /** "sms" or "whatsapp": key in config/notifications.php. */
    abstract protected function cle(): string;

    abstract protected function texte(NotificationDag $notification, object $notifiable): ?string;

    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notification instanceof NotificationDag) {
            return;
        }

        $numero = $notification->adresse($notifiable, $this->cle());
        $texte = $this->texte($notification, $notifiable);

        if ($numero === null || $texte === null) {
            return;
        }

        $this->passerelle()->envoyer($numero, $texte);
    }

    private function passerelle(): PasserelleMessage
    {
        $fournisseur = config('notifications.messagerie.'.$this->cle());
        $classe = config("notifications.messagerie.fournisseurs.{$fournisseur}");

        return app($classe);
    }
}
