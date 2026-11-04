<?php

namespace App\Console\Commands;

use App\Models\NotificationLog;
use App\Models\Seance;
use App\Notifications\RappelSeanceNotification;
use App\Support\Notifications\Notifier;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('notifications:rappels-seances')]
#[Description('Rappelle aux apprenants inscrits leur séance du lendemain')]
class RappelerSeances extends Command
{
    public function handle(): int
    {
        $envoyes = 0;

        $seances = Seance::query()
            ->whereDate('date', now()->addDay()->toDateString())
            ->with(['formationSession.formation', 'formationSession.inscriptions.apprenant.user'])
            ->get();

        foreach ($seances as $seance) {
            $inscrits = $seance->formationSession->inscriptions->where('statut', 'VALIDEE');

            foreach ($inscrits as $inscription) {
                $utilisateur = $inscription->apprenant->user;
                $notification = new RappelSeanceNotification($seance);

                // Running the command twice the same day must not remind twice.
                $dejaRappele = NotificationLog::query()
                    ->where('user_id', $utilisateur->id)
                    ->where('evenement', $notification->evenement())
                    ->where('message', $notification->message())
                    ->exists();

                if ($dejaRappele) {
                    continue;
                }

                Notifier::envoyer($utilisateur, $notification);
                $envoyes++;
            }
        }

        $this->info("{$envoyes} rappel(s) de séance envoyé(s).");

        return self::SUCCESS;
    }
}
