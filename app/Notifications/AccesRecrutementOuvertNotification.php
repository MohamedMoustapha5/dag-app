<?php

namespace App\Notifications;

use App\Models\AccesRecrutement;

/**
 * Tells an entreprise or an organisation that its recruitment subscription
 * is active (paid, or offered by DAG): job offers and candidates are open
 * in its espace until the end date.
 */
class AccesRecrutementOuvertNotification extends NotificationDag
{
    public function __construct(private AccesRecrutement $acces)
    {
        parent::__construct();
    }

    public function evenement(): string
    {
        return 'recrutement.acces';
    }

    public function titre(): string
    {
        return 'Votre accès recrutement est actif';
    }

    public function message(): string
    {
        $fin = $this->acces->fin?->locale('fr')->translatedFormat('j F Y');

        return $this->acces->origine === 'OFFERT'
            ? "L'équipe DAG vous offre {$this->acces->libelle} d'accès recrutement, jusqu'au {$fin} : publiez vos offres d'emploi et suivez vos candidats."
            : "Merci, votre abonnement recrutement ({$this->acces->libelle}) est actif jusqu'au {$fin} : publiez vos offres d'emploi et suivez vos candidats.";
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
