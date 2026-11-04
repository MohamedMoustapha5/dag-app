<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Canaux\SmsChannel;
use App\Notifications\Canaux\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Base of every DAG notification. A subclass only says what happened (title,
 * message, link, preference); this class decides which channels carry it,
 * honours the recipient's preferences and builds the branded e-mail, so a new
 * channel (SMS, WhatsApp) is available to every notification at once.
 */
abstract class NotificationDag extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->connection = config('notifications.file_attente');
    }

    /** Stable identifier of the event, stored in the notification journal. */
    abstract public function evenement(): string;

    abstract public function titre(): string;

    abstract public function message(): string;

    /** Absolute link opened from the e-mail, if any. */
    public function url(object $notifiable): ?string
    {
        return null;
    }

    public function libelleAction(): string
    {
        return 'Ouvrir mon espace';
    }

    /**
     * Column of `users` holding the recipient's opt-in for this kind of
     * notification (notif_devis, notif_facture…), or null when it cannot be
     * switched off.
     */
    public function preference(): ?string
    {
        return null;
    }

    /**
     * @return list<string|class-string>
     */
    public function via(object $notifiable): array
    {
        $canaux = [];

        // The bell of the espace always receives it: the preferences only
        // switch off the messages sent outside the application.
        if (method_exists($notifiable, 'notifications')) {
            $canaux[] = 'database';
        }

        if (! $this->accepte($notifiable)) {
            return $canaux;
        }

        if (config('notifications.canaux.email') && $this->adresse($notifiable, 'mail') !== null) {
            $canaux[] = 'mail';
        }

        if (config('notifications.canaux.sms') && $this->toSms($notifiable) !== null
            && $this->adresse($notifiable, 'sms') !== null) {
            $canaux[] = SmsChannel::class;
        }

        if (config('notifications.canaux.whatsapp') && $this->toWhatsApp($notifiable) !== null
            && $this->adresse($notifiable, 'whatsapp') !== null) {
            $canaux[] = WhatsAppChannel::class;
        }

        return $canaux;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->titre())
            ->greeting($this->salutation($notifiable))
            ->line($this->message());

        foreach ($this->lignes() as $ligne) {
            $mail->line($ligne);
        }

        $url = $this->url($notifiable);

        if ($url !== null) {
            $mail->action($this->libelleAction(), $url);
        }

        return $mail->salutation('L\'équipe DAG — Digital Agency Group');
    }

    /**
     * Content stored for the bell of the espace. The link is relative so it
     * stays valid whichever host serves the application.
     *
     * @return array{evenement: string, titre: string, message: string, url: string|null}
     */
    public function toDatabase(object $notifiable): array
    {
        $url = $this->url($notifiable);
        $chemin = $url !== null ? (string) parse_url($url, PHP_URL_PATH) : null;
        $requete = $url !== null ? parse_url($url, PHP_URL_QUERY) : null;

        return [
            'evenement' => $this->evenement(),
            'titre' => $this->titre(),
            'message' => $this->message(),
            'url' => $chemin !== null && $chemin !== '' ? $chemin.(is_string($requete) ? "?{$requete}" : '') : null,
        ];
    }

    /**
     * Short text for the SMS and WhatsApp channels.
     */
    public function toSms(object $notifiable): ?string
    {
        return $this->message();
    }

    public function toWhatsApp(object $notifiable): ?string
    {
        return $this->message();
    }

    /**
     * Extra paragraphs added to the e-mail after the main message.
     *
     * @return list<string>
     */
    protected function lignes(): array
    {
        return [];
    }

    /**
     * Address a channel writes to (e-mail address, phone number), or null when
     * the recipient has none for it.
     */
    public function adresse(object $notifiable, string $canal): ?string
    {
        if (! method_exists($notifiable, 'routeNotificationFor')) {
            return null;
        }

        $adresse = $notifiable->routeNotificationFor($canal, $this);

        return is_string($adresse) && $adresse !== '' ? $adresse : null;
    }

    private function salutation(object $notifiable): string
    {
        $nom = $notifiable->name ?? $notifiable->nom ?? null;

        return $nom !== null ? "Bonjour {$nom}," : 'Bonjour,';
    }

    private function accepte(object $notifiable): bool
    {
        $preference = $this->preference();

        if ($preference === null || ! $notifiable instanceof User) {
            return true;
        }

        return (bool) $notifiable->{$preference};
    }
}
