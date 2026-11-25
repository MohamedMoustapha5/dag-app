<?php

namespace App\Notifications;

use App\Models\Partenariat;

/**
 * The partnership contract is ready for the client to sign: DAG accepted
 * its request, or proposes the partnership itself.
 */
class PartenariatContratNotification extends NotificationDag
{
    public function __construct(private Partenariat $partenariat)
    {
        parent::__construct();
    }

    public function evenement(): string
    {
        return $this->partenariat->origine === 'PROPOSITION' ? 'partenariat.propose' : 'partenariat.accepte';
    }

    public function preference(): ?string
    {
        return 'notif_devis';
    }

    public function titre(): string
    {
        return $this->partenariat->origine === 'PROPOSITION'
            ? 'Digital Agency Group vous propose un partenariat'
            : 'Votre demande de partenariat est acceptée';
    }

    public function message(): string
    {
        $duree = $this->partenariat->pourUneDuree();

        return $this->partenariat->origine === 'PROPOSITION'
            ? "L'équipe DAG souhaite faire de votre structure un partenaire officiel, {$duree}. Le contrat de partenariat vous attend dans votre espace : lisez-le et signez-le pour l'accepter."
            : "L'équipe DAG a accepté votre demande de partenariat, {$duree}. Le contrat de partenariat vous attend dans votre espace : lisez-le et signez-le.";
    }

    protected function lignes(): array
    {
        return ['Une fois votre signature validée par DAG, votre structure devient « Partenaire officiel de Digital Agency Group ».'];
    }

    public function url(object $notifiable): ?string
    {
        return $this->partenariat->contrat_id !== null
            ? route('espace-client.contrats.show', $this->partenariat->contrat_id)
            : route('espace-client.partenariat');
    }

    public function libelleAction(): string
    {
        return 'Lire et signer le contrat';
    }
}
