<?php

namespace App\Console\Commands;

use App\Models\Contrat;
use App\Notifications\ContratASignerNotification;
use App\Support\Notifications\Notifier;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('notifications:relancer-contrats')]
#[Description('Relance les clients dont le contrat envoyé attend toujours leur signature')]
class RelancerContrats extends Command
{
    public function handle(): int
    {
        $delai = now()->subDays((int) config('notifications.relance_contrat_jours'));
        $relances = 0;

        $contrats = Contrat::query()
            ->where('statut', 'ENVOYE')
            ->where('updated_at', '<=', $delai)
            ->where(fn ($requete) => $requete->whereNull('derniere_relance_at')->orWhere('derniere_relance_at', '<=', $delai))
            ->with(['client.user', 'apprenant.user', 'entreprise.client.user'])
            ->get();

        foreach ($contrats as $contrat) {
            Notifier::envoyer(Notifier::utilisateurDe($contrat), new ContratASignerNotification($contrat, relance: true));

            // Straight to the query builder: the reminder date must not count as an edit
            // of the contract (its updated_at is when it was sent).
            Contrat::query()->whereKey($contrat->getKey())->toBase()->update(['derniere_relance_at' => now()]);

            $relances++;
        }

        $this->info("{$relances} relance(s) de contrat envoyée(s).");

        return self::SUCCESS;
    }
}
