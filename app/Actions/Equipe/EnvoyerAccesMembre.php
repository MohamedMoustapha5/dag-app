<?php

namespace App\Actions\Equipe;

use App\Models\MembreEquipe;
use App\Notifications\AccesEquipeNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Gives a team member a new random password and e-mails it right away.
 *
 * The password only changes when its e-mail has left: if the sending
 * fails, the exception goes up and the account keeps its previous one.
 * The member's open sessions end, so the old password opens nothing more.
 */
class EnvoyerAccesMembre
{
    public function handle(MembreEquipe $membre, bool $administrateur = false): void
    {
        $motDePasse = Str::password(16, symbols: false);

        DB::transaction(function () use ($membre, $motDePasse, $administrateur): void {
            $membre->password = $motDePasse;
            $membre->save();

            DB::table('sessions')->where('user_id', $membre->id)->delete();

            $membre->notifyNow(new AccesEquipeNotification($motDePasse, $administrateur));
        });
    }
}
