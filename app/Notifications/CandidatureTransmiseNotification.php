<?php

namespace App\Notifications;

use App\Models\Candidature;

class CandidatureTransmiseNotification extends NotificationDag
{
    public function __construct(private Candidature $candidature)
    {
        parent::__construct();
    }

    public function evenement(): string
    {
        return 'candidature.transmise';
    }

    public function titre(): string
    {
        return 'DAG vous propose un candidat';
    }

    public function message(): string
    {
        return "DAG vous transmet un candidat pour votre offre « {$this->candidature->offre->titre} ». Consultez son dossier, puis sélectionnez-le ou refusez-le.";
    }

    public function url(object $notifiable): ?string
    {
        return route('espace-client.candidatures.show', $this->candidature);
    }

    public function libelleAction(): string
    {
        return 'Voir le dossier du candidat';
    }
}
