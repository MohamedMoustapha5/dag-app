<?php

namespace App\Notifications;

use App\Models\Attestation;

class AttestationDisponibleNotification extends NotificationDag
{
    public function __construct(private Attestation $attestation)
    {
        parent::__construct();
    }

    public function evenement(): string
    {
        return 'attestation.disponible';
    }

    public function preference(): ?string
    {
        return 'notif_seances';
    }

    public function titre(): string
    {
        return 'Votre attestation est disponible';
    }

    public function message(): string
    {
        return "Félicitations : votre attestation pour la formation « {$this->attestation->formation->nom} » a été validée et peut être téléchargée.";
    }

    public function url(object $notifiable): ?string
    {
        return route('espace-apprenant.dossier');
    }

    public function libelleAction(): string
    {
        return 'Télécharger mon attestation';
    }
}
