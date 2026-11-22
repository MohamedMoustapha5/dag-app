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

        $lignes[] = 'Votre devis (facture proforma) vous attend dans votre espace : consultez-le et validez-le en un clic.';

        $lignes[] = $this->demande->estParticulier()
            ? 'Une fois le devis validé, vous réglez la facture en ligne et la prestation démarre.'
            : 'Une fois le devis validé, votre contrat vous est envoyé pour signature ; après sa validation par DAG, vous réglez la facture en ligne.';

        return $lignes;
    }

    public function url(object $notifiable): ?string
    {
        return route('espace-client.demandes.show', $this->demande);
    }

    public function libelleAction(): string
    {
        return 'Voir et valider le devis';
    }
}
