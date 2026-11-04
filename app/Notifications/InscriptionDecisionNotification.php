<?php

namespace App\Notifications;

use App\Models\Inscription;

class InscriptionDecisionNotification extends NotificationDag
{
    public function __construct(private Inscription $inscription, private bool $validee)
    {
        parent::__construct();
    }

    public function evenement(): string
    {
        return $this->validee ? 'inscription.validee' : 'inscription.refusee';
    }

    public function preference(): ?string
    {
        return 'notif_seances';
    }

    public function titre(): string
    {
        return $this->validee
            ? 'Votre inscription est validée'
            : "Votre inscription n'a pas pu être retenue";
    }

    public function message(): string
    {
        $formation = $this->inscription->formation->nom;

        return $this->validee
            ? "Votre inscription à la formation « {$formation} » est validée. Retrouvez le calendrier des séances dans votre espace."
            : "Votre inscription à la formation « {$formation} » n'a pas pu être retenue. Contactez-nous pour en savoir plus.";
    }

    public function url(object $notifiable): ?string
    {
        return route('espace-apprenant.inscriptions');
    }

    public function libelleAction(): string
    {
        return 'Voir mes inscriptions';
    }
}
