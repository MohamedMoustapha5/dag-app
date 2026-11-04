<?php

namespace App\Listeners;

use App\Models\NotificationLog;
use App\Models\User;
use App\Notifications\Canaux\SmsChannel;
use App\Notifications\Canaux\WhatsAppChannel;
use App\Notifications\NotificationDag;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;

/**
 * Keeps a trace of every DAG notification (channel, recipient, outcome) in the
 * notification journal, whichever channel carried it.
 */
// Method names deliberately do not start with "handle": the listeners are registered
// explicitly in AppServiceProvider, and Laravel would otherwise discover them a second time.
class JournaliserNotification
{
    public function quandEnvoyee(NotificationSent $evenement): void
    {
        $this->enregistrer($evenement, 'ENVOYE');
    }

    public function quandEchouee(NotificationFailed $evenement): void
    {
        $this->enregistrer($evenement, 'ECHEC');
    }

    private function enregistrer(NotificationSent|NotificationFailed $evenement, string $statut): void
    {
        $notification = $evenement->notification;

        // The bell of the espace is not a message sent outside the application.
        if (! $notification instanceof NotificationDag || $evenement->channel === 'database') {
            return;
        }

        $canal = match ($evenement->channel) {
            SmsChannel::class => 'SMS',
            WhatsAppChannel::class => 'WHATSAPP',
            default => 'EMAIL',
        };

        $destinataire = $evenement->notifiable;
        $adresse = $notification->adresse($destinataire, $canal === 'EMAIL' ? 'mail' : strtolower($canal));

        NotificationLog::create([
            'user_id' => $destinataire instanceof User ? $destinataire->id : null,
            'destinataire' => $adresse,
            'evenement' => $notification->evenement(),
            'canal' => $canal,
            'titre' => $notification->titre(),
            'message' => $notification->message(),
            'statut' => $statut,
            'tentatives' => 1,
            'envoye_at' => $statut === 'ENVOYE' ? now() : null,
        ]);
    }
}
