<?php

namespace App\Console\Commands;

use App\Models\Paiement;
use App\Support\Paiements\PaiementService;
use App\Support\Paiements\PasserelleIndisponible;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Asks the active provider for the status of every online payment still
 * pending, so a payer who paid then closed the page before coming back is
 * credited anyway. Safety net behind the webhook: it only reads statuses,
 * and confirming stays idempotent (PaiementService::confirmer).
 */
#[Signature('paiements:verifier-en-attente')]
#[Description('Vérifie auprès du prestataire les paiements en ligne encore en attente')]
class VerifierPaiementsEnAttente extends Command
{
    public function handle(PaiementService $paiements): int
    {
        $passerelle = $paiements->passerelle();
        $bilan = ['confirmés' => 0, 'échoués' => 0, 'toujours en attente' => 0, 'non conformes' => 0, 'injoignables' => 0];

        $enAttente = Paiement::query()
            ->where('statut', 'EN_ATTENTE')
            ->whereNotNull('reference_transaction')
            // A payer still on the provider's page is left to the result page.
            ->where('created_at', '<=', now()->subMinutes(2))
            ->where('created_at', '>=', now()->subDays((int) config('paiements.verification_en_attente_jours')))
            ->oldest()
            ->get();

        foreach ($enAttente as $paiement) {
            // A success for the wrong amount is logged for the team once,
            // not again every ten minutes.
            if (Cache::has($this->cleNonConforme($paiement))) {
                $bilan['non conformes']++;

                continue;
            }

            try {
                $resultat = $passerelle->verifier($paiement->reference_transaction);
            } catch (PasserelleIndisponible $erreur) {
                Log::warning('Vérification d\'un paiement en attente impossible', [
                    'paiement' => $paiement->id,
                    'erreur' => $erreur->getMessage(),
                ]);
                $bilan['injoignables']++;

                continue;
            }

            if (! $paiements->appliquerResultat($paiement, $resultat)) {
                Cache::put($this->cleNonConforme($paiement), true, now()->addDays((int) config('paiements.verification_en_attente_jours') + 1));
                $bilan['non conformes']++;

                continue;
            }

            $bilan[match ($paiement->fresh()->statut) {
                'CONFIRME' => 'confirmés',
                'ECHEC' => 'échoués',
                default => 'toujours en attente',
            }]++;
        }

        $this->info(collect($bilan)->map(fn (int $nombre, string $libelle) => "{$nombre} {$libelle}")->implode(', ').'.');

        return self::SUCCESS;
    }

    private function cleNonConforme(Paiement $paiement): string
    {
        return "paiements:non-conforme:{$paiement->id}";
    }
}
