<?php

namespace App\Notifications;

/**
 * Internal alert for the team: a new demande, a payment received, a contract
 * signed. Always sent, since it cannot be switched off from a client space.
 */
class AlerteEquipeNotification extends NotificationDag
{
    public function __construct(
        private string $evenement,
        private string $titre,
        private string $message,
        private ?string $url = null,
    ) {
        parent::__construct();
    }

    public function evenement(): string
    {
        return $this->evenement;
    }

    public function titre(): string
    {
        return $this->titre;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function url(object $notifiable): ?string
    {
        return $this->url;
    }

    public function libelleAction(): string
    {
        return "Ouvrir dans l'administration";
    }

    public function toSms(object $notifiable): ?string
    {
        return null;
    }

    public function toWhatsApp(object $notifiable): ?string
    {
        return null;
    }
}
