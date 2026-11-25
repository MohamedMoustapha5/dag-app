<?php

namespace App\Notifications;

use App\Models\Partenariat;

/**
 * DAG declined the client's partnership request, with its reason.
 */
class PartenariatRefuseNotification extends NotificationDag
{
    public function __construct(private Partenariat $partenariat)
    {
        parent::__construct();
    }

    public function evenement(): string
    {
        return 'partenariat.refuse';
    }

    public function titre(): string
    {
        return 'Votre demande de partenariat n’a pas été retenue';
    }

    public function message(): string
    {
        return "L'équipe DAG a étudié votre demande de partenariat {$this->partenariat->reference} et ne peut pas y donner suite pour le moment. Motif : {$this->partenariat->motif_refus}";
    }

    protected function lignes(): array
    {
        return ['Vous pourrez présenter une nouvelle demande depuis votre espace lorsque votre situation aura évolué.'];
    }

    public function url(object $notifiable): ?string
    {
        return route('espace-client.partenariat');
    }

    public function libelleAction(): string
    {
        return 'Voir ma demande';
    }
}
