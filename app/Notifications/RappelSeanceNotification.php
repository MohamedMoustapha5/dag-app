<?php

namespace App\Notifications;

use App\Models\Seance;
use Illuminate\Support\Carbon;

class RappelSeanceNotification extends NotificationDag
{
    public function __construct(private Seance $seance)
    {
        parent::__construct();
    }

    public function evenement(): string
    {
        return 'seance.rappel';
    }

    public function preference(): ?string
    {
        return 'notif_seances';
    }

    public function titre(): string
    {
        return "Rappel : séance demain — {$this->seance->formationSession->formation->nom}";
    }

    public function message(): string
    {
        $session = $this->seance->formationSession;
        $date = Carbon::parse($this->seance->date)->format('d/m/Y');
        $titre = $this->seance->titre !== null ? " « {$this->seance->titre} »" : '';
        $lieu = $session->lieu !== null ? " ({$session->lieu})" : " à {$session->ville}";

        return "Votre séance{$titre} de la formation « {$session->formation->nom} » a lieu demain, le {$date}{$lieu}.";
    }

    public function url(object $notifiable): ?string
    {
        return route('espace-apprenant.inscriptions');
    }

    public function libelleAction(): string
    {
        return 'Voir mes séances';
    }
}
