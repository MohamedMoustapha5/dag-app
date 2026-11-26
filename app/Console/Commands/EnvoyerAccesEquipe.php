<?php

namespace App\Console\Commands;

use App\Enums\MembreEquipeRole;
use App\Models\MembreEquipe;
use App\Notifications\AccesEquipeNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Gives a new random password to team accounts and e-mails it to each one.
 *
 * Without argument, it prepares the administration account of
 * config('entreprise.administrateur'): every right (CEO role), hidden from
 * « L'équipe » on the public site, created if needed. With an address, it
 * sends a new password to that team member. With --tous, it does both for
 * the administrator and every active member; the production seed runs it
 * once, when it creates the team.
 *
 * A password only changes when its e-mail has left: if the sending fails,
 * the account keeps its previous one.
 */
#[Signature('equipe:envoyer-acces
    {email? : Adresse d\'un membre de l\'équipe (sans elle : le compte administrateur)}
    {--tous : Le compte administrateur puis chaque membre actif de l\'équipe}')]
#[Description('Donne un mot de passe aléatoire et l\'envoie par e-mail : compte administrateur, un membre ou toute l\'équipe')]
class EnvoyerAccesEquipe extends Command
{
    public function handle(): int
    {
        $argument = $this->argument('email');
        $email = is_string($argument) && $argument !== '' ? $argument : null;

        if ($email !== null && $this->option('tous')) {
            $this->error('Donnez une adresse ou --tous, pas les deux.');

            return self::INVALID;
        }

        if ($email !== null) {
            $membre = MembreEquipe::where('email', $email)->first();

            if ($membre === null) {
                $this->error("Aucun membre de l'équipe n'a l'adresse {$email}.");

                return self::FAILURE;
            }

            if (! $membre->estActif()) {
                $this->error("Le compte de {$email} est désactivé : réactivez-le d'abord depuis la page Équipe.");

                return self::FAILURE;
            }

            return $this->envoyer($membre) ? self::SUCCESS : self::FAILURE;
        }

        $administrateur = $this->compteAdministrateur();
        $reussi = $this->envoyer($administrateur, administrateur: true);

        if ($this->option('tous')) {
            $membres = MembreEquipe::where('statut', 'ACTIF')
                ->whereKeyNot($administrateur->getKey())
                ->orderBy('created_at')
                ->get();

            foreach ($membres as $membre) {
                $reussi = $this->envoyer($membre) && $reussi;
            }
        }

        return $reussi ? self::SUCCESS : self::FAILURE;
    }

    /**
     * The administration account, unsaved: it is only stored with the
     * password whose e-mail has left.
     */
    private function compteAdministrateur(): MembreEquipe
    {
        $membre = MembreEquipe::firstOrNew(['email' => config('entreprise.administrateur.email')]);

        $membre->name ??= config('entreprise.administrateur.nom');
        $membre->role = MembreEquipeRole::Promotrice;
        $membre->statut = 'ACTIF';
        $membre->visible_sur_le_site = false;

        return $membre;
    }

    private function envoyer(MembreEquipe $membre, bool $administrateur = false): bool
    {
        $motDePasse = Str::password(16, symbols: false);

        try {
            DB::transaction(function () use ($membre, $motDePasse, $administrateur): void {
                $membre->password = $motDePasse;
                $membre->save();

                $membre->notifyNow(new AccesEquipeNotification($motDePasse, $administrateur));
            });
        } catch (Throwable $exception) {
            report($exception);
            $this->error("{$membre->email} : l'e-mail n'a pas pu partir ({$exception->getMessage()}). Le mot de passe n'a pas changé.");

            return false;
        }

        $this->info("Accès envoyé à {$membre->email}.");

        return true;
    }
}
