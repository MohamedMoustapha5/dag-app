<?php

namespace App\Notifications;

use App\Models\Contrat;

class ContratASignerNotification extends NotificationDag
{
    public function __construct(private Contrat $contrat, private bool $relance = false)
    {
        parent::__construct();
    }

    public function evenement(): string
    {
        return $this->relance ? 'contrat.relance' : 'contrat.a_signer';
    }

    public function preference(): ?string
    {
        return 'notif_devis';
    }

    public function titre(): string
    {
        return $this->relance
            ? "Rappel : votre contrat attend votre signature — {$this->contrat->reference}"
            : "Votre contrat est prêt à signer — {$this->contrat->reference}";
    }

    public function message(): string
    {
        return $this->contrat->demande_id !== null
            ? "Le contrat « {$this->contrat->libelleObjet()} » est disponible dans votre espace. Signez-le pour régler votre facture et lancer la prestation."
            : "Le contrat « {$this->contrat->libelleObjet()} » est disponible dans votre espace. Sa signature est nécessaire pour que la prestation démarre.";
    }

    protected function lignes(): array
    {
        return ["Lisez-le, importez l'image de votre signature, puis confirmez : cela ne prend que quelques minutes."];
    }

    public function url(object $notifiable): ?string
    {
        return route('espace-client.contrats.show', $this->contrat);
    }

    public function libelleAction(): string
    {
        return 'Lire et signer le contrat';
    }
}
