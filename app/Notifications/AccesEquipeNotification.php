<?php

namespace App\Notifications;

use App\Models\MembreEquipe;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Gives a team member their sign-in details for the administration. Sent
 * by e-mail only and right away (equipe:envoyer-acces): the password never
 * waits in the queue nor lands in the bell of the espace.
 */
class AccesEquipeNotification extends Notification
{
    public function __construct(public readonly string $motDePasse, public readonly bool $administrateur = false)
    {
        $this->locale = 'fr';
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(MembreEquipe $notifiable): MailMessage
    {
        $compte = $this->administrateur
            ? 'Votre compte administrateur (tous les droits) est prêt.'
            : "Votre compte « {$notifiable->role->label()} » est prêt.";

        return (new MailMessage)
            ->subject('Vos accès à l’administration Digital Agency Group')
            ->greeting("Bonjour {$notifiable->name},")
            ->line("{$compte} Voici vos identifiants pour l’espace de l’équipe :")
            ->line("Adresse e-mail : **{$notifiable->email}**")
            ->line("Mot de passe provisoire : **{$this->motDePasse}**")
            ->action('Me connecter', route('admin.connexion'))
            ->line('Changez ce mot de passe dès votre première connexion (icône clé « Mon mot de passe », à côté de votre nom), puis supprimez cet e-mail. Ne le transférez à personne.')
            ->salutation('L\'équipe Digital Agency Group');
    }
}
