<?php

namespace App\Notifications;

use App\Models\Contrat;

/**
 * Tells the client that DAG validated the contract they signed: the
 * facture of the demande can now be paid.
 */
class ContratValideNotification extends NotificationDag
{
    public function __construct(private Contrat $contrat)
    {
        parent::__construct();
    }

    public function evenement(): string
    {
        return 'contrat.valide';
    }

    public function preference(): ?string
    {
        return 'notif_devis';
    }

    public function titre(): string
    {
        return "Contrat validé : réglez votre facture — {$this->contrat->reference}";
    }

    public function message(): string
    {
        return "L'équipe DAG a validé le contrat « {$this->contrat->libelleObjet()} » que vous avez signé. Vous pouvez maintenant régler votre facture pour lancer la prestation.";
    }

    protected function lignes(): array
    {
        return ['Paiement en ligne depuis votre espace : Orange Money, MTN Mobile Money ou carte.'];
    }

    public function url(object $notifiable): ?string
    {
        return $this->contrat->demande_id !== null
            ? route('espace-client.demandes.show', $this->contrat->demande_id)
            : route('espace-client.contrats.show', $this->contrat);
    }

    public function libelleAction(): string
    {
        return 'Régler ma facture';
    }
}
