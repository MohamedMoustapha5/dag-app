<?php

namespace App\Notifications;

/**
 * Tells an Entreprise client that DAG made it an official recruitment
 * partner: job offers and candidates are now open in its espace.
 */
class PartenariatValideNotification extends NotificationDag
{
    public function evenement(): string
    {
        return 'partenariat.valide';
    }

    public function titre(): string
    {
        return 'Votre entreprise est partenaire recrutement de DAG';
    }

    public function message(): string
    {
        return "L'équipe DAG a validé votre entreprise comme partenaire recrutement. Vous pouvez maintenant publier vos offres d'emploi et recevoir les candidats que DAG vous transmet.";
    }

    protected function lignes(): array
    {
        return ['Chaque offre est vérifiée par DAG avant sa mise en ligne.'];
    }

    public function url(object $notifiable): ?string
    {
        return route('espace-client.offres.index');
    }

    public function libelleAction(): string
    {
        return 'Publier une offre';
    }
}
