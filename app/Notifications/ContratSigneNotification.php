<?php

namespace App\Notifications;

use App\Models\Contrat;

class ContratSigneNotification extends NotificationDag
{
    public function __construct(private Contrat $contrat)
    {
        parent::__construct();
    }

    public function evenement(): string
    {
        return 'contrat.signe';
    }

    public function preference(): ?string
    {
        return 'notif_devis';
    }

    public function titre(): string
    {
        return "Contrat signé — {$this->contrat->reference}";
    }

    public function message(): string
    {
        return "Merci : votre contrat « {$this->contrat->libelleObjet()} » a bien été signé. La prestation peut démarrer.";
    }

    protected function lignes(): array
    {
        return ['Vous pouvez télécharger le PDF signé à tout moment depuis votre espace.'];
    }

    public function url(object $notifiable): ?string
    {
        return route('espace-client.contrats.show', $this->contrat);
    }

    public function libelleAction(): string
    {
        return 'Voir mon contrat';
    }
}
