<?php

namespace App\Notifications;

use App\Models\Partenariat;

/**
 * DAG validated the signed partnership contract: the client is now an
 * official partner until the end of the partnership.
 */
class PartenariatValideNotification extends NotificationDag
{
    public function __construct(private Partenariat $partenariat)
    {
        parent::__construct();
    }

    public function evenement(): string
    {
        return 'partenariat.valide';
    }

    public function titre(): string
    {
        return 'Vous êtes Partenaire officiel de Digital Agency Group';
    }

    public function message(): string
    {
        $periode = $this->partenariat->fin !== null
            ? "jusqu'au {$this->partenariat->fin->settings(['locale' => 'fr'])->translatedFormat('j F Y')}"
            : 'pour une durée indéterminée';

        return "L'équipe DAG a validé votre contrat de partenariat : votre structure est Partenaire officiel de Digital Agency Group {$periode}. Bienvenue parmi nos partenaires !";
    }

    protected function lignes(): array
    {
        return ['Votre contrat signé est disponible dans votre espace, rubrique « Partenariat DAG ».'];
    }

    public function url(object $notifiable): ?string
    {
        return route('espace-client.partenariat');
    }

    public function libelleAction(): string
    {
        return 'Voir mon partenariat';
    }
}
