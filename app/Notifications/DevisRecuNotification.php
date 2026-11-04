<?php

namespace App\Notifications;

use App\Models\Demande;

class DevisRecuNotification extends NotificationDag
{
    public function __construct(private Demande $demande)
    {
        parent::__construct();
    }

    public function evenement(): string
    {
        return 'devis.recu';
    }

    public function preference(): ?string
    {
        return 'notif_devis';
    }

    public function titre(): string
    {
        return "Votre devis est prêt — {$this->demande->reference}";
    }

    public function message(): string
    {
        $montant = number_format((float) $this->demande->montant_negocie, 0, ',', ' ');

        return "Notre équipe a préparé le devis de votre demande « {$this->demande->libelleObjet()} » : {$montant} FCFA.";
    }

    protected function lignes(): array
    {
        $lignes = [];

        if ($this->demande->message_devis !== null && trim($this->demande->message_devis) !== '') {
            $lignes[] = $this->demande->message_devis;
        }

        $lignes[] = 'Vous pouvez payer directement depuis votre espace : Orange Money, MTN Mobile Money ou carte.';

        return $lignes;
    }

    public function url(object $notifiable): ?string
    {
        return route('espace-client.demandes.show', $this->demande);
    }

    public function libelleAction(): string
    {
        return 'Voir et payer le devis';
    }
}
