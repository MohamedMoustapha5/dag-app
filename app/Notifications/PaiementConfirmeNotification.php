<?php

namespace App\Notifications;

use App\Models\Facture;
use App\Models\User;

class PaiementConfirmeNotification extends NotificationDag
{
    public function __construct(private Facture $facture, private float $montant)
    {
        parent::__construct();
    }

    public function evenement(): string
    {
        return 'paiement.confirme';
    }

    public function preference(): ?string
    {
        return 'notif_facture';
    }

    public function titre(): string
    {
        return "Paiement reçu — {$this->facture->reference}";
    }

    public function message(): string
    {
        $montant = number_format($this->montant, 0, ',', ' ');

        return "Nous avons bien reçu votre paiement de {$montant} FCFA pour « {$this->facture->libelleObjet()} ».";
    }

    protected function lignes(): array
    {
        return $this->facture->statut === 'PAYEE'
            ? ['Votre facture est entièrement réglée : son reçu est disponible au téléchargement dans votre espace.']
            : ['Il reste un solde à régler sur cette facture.'];
    }

    public function url(object $notifiable): ?string
    {
        return $notifiable instanceof User && $notifiable->apprenant !== null
            ? route('espace-apprenant.factures')
            : route('espace-client.factures');
    }

    public function libelleAction(): string
    {
        return 'Voir mes factures';
    }
}
