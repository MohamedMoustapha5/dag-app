<?php

namespace App\Notifications;

use App\Models\MessageSupport;
use App\Models\TicketSupport;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * The DAG team answered a support request. An account holder follows the
 * link to the thread in their espace; a visitor of the public contact form
 * has no espace, so the answer itself travels in the e-mail.
 */
class SupportReponseNotification extends NotificationDag
{
    public function __construct(private TicketSupport $ticket, private MessageSupport $reponse)
    {
        parent::__construct();
    }

    public function evenement(): string
    {
        return 'support.reponse';
    }

    public function titre(): string
    {
        return "Réponse de l'équipe DAG — {$this->ticket->reference}";
    }

    public function message(): string
    {
        return "Notre équipe a répondu à votre demande « {$this->ticket->sujet} ».";
    }

    protected function lignes(): array
    {
        return $this->ticket->user_id === null ? [$this->reponse->contenu] : [Str::limit($this->reponse->contenu, 300)];
    }

    public function url(object $notifiable): ?string
    {
        if (! $notifiable instanceof User) {
            return null;
        }

        return $notifiable->estApprenant()
            ? route('espace-apprenant.support.show', $this->ticket)
            : route('espace-client.support.show', $this->ticket);
    }

    public function libelleAction(): string
    {
        return 'Voir la conversation';
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
