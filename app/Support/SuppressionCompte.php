<?php

namespace App\Support;

use App\Models\Apprenant;
use App\Models\Attestation;
use App\Models\Client;
use App\Models\Contrat;
use App\Models\Facture;
use App\Models\NotificationLog;
use App\Models\TicketSupport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Closing a client or apprenant account, whoever asks for it (the account
 * holder or DAG staff). Factures, contrats and attestations are legal and
 * accounting records DAG must keep: an account that has any is anonymised
 * (personal data erased, records kept attached to a placeholder identity)
 * instead of deleted. An account without any is simply deleted.
 */
class SuppressionCompte
{
    public const NOM_ANONYME = 'Compte supprimé';

    /**
     * @return bool true when the account was anonymised, false when deleted.
     */
    public static function fermer(User $user): bool
    {
        // Support threads are personal exchanges, not legal records.
        TicketSupport::where('user_id', $user->id)->delete();

        if (! self::aDesPiecesAConserver($user)) {
            self::supprimerFichiers($user);
            $user->delete();

            return false;
        }

        DB::transaction(fn () => self::anonymiser($user));

        return true;
    }

    public static function aDesPiecesAConserver(User $user): bool
    {
        $client = $user->client;
        $apprenant = $user->apprenant;

        if ($client !== null
            && (Facture::where('client_id', $client->id)->exists() || Contrat::where('client_id', $client->id)->exists())) {
            return true;
        }

        return $apprenant !== null
            && (Facture::where('apprenant_id', $apprenant->id)->exists()
                || Contrat::where('apprenant_id', $apprenant->id)->exists()
                || Attestation::where('apprenant_id', $apprenant->id)->exists());
    }

    private static function anonymiser(User $user): void
    {
        self::supprimerFichiers($user);

        if ($user->client !== null) {
            self::anonymiserClient($user->client);
        }

        if ($user->apprenant !== null) {
            self::anonymiserApprenant($user->apprenant);
        }

        NotificationLog::where('user_id', $user->id)->update(['destinataire' => null]);

        $user->notifications()->delete();
        $user->passkeys()->delete();
        DB::table('sessions')->where('user_id', $user->id)->delete();

        $user->forceFill([
            'name' => self::NOM_ANONYME,
            'email' => 'supprime-'.$user->id.'@anonyme.invalid',
            'password' => Hash::make(Str::random(64)),
            'remember_token' => null,
            'email_verified_at' => null,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'notif_devis' => false,
            'notif_facture' => false,
            'notif_offres' => false,
            'notif_seances' => false,
            'notif_candidatures' => false,
            'notif_newsletter' => false,
        ])->save();
    }

    private static function anonymiserClient(Client $client): void
    {
        $client->update([
            'nom' => self::NOM_ANONYME,
            'secteur_activite' => null,
            'ville' => null,
            'telephone' => null,
            'statut' => 'INACTIF',
        ]);
    }

    private static function anonymiserApprenant(Apprenant $apprenant): void
    {
        $apprenant->update([
            'date_naissance' => null,
            'telephone' => null,
            'ville' => null,
            'cv_path' => null,
            'consentement_cv' => false,
        ]);

        $apprenant->candidatureSpontanee?->update([
            'nom' => self::NOM_ANONYME,
            'email' => 'supprime-'.$apprenant->id.'@anonyme.invalid',
            'telephone' => '—',
            'portfolio' => null,
            'message' => null,
        ]);
    }

    /**
     * CVs are personal data with no legal retention: they go in every case.
     * Signatures stay, as the evidence of the contracts DAG keeps.
     */
    private static function supprimerFichiers(User $user): void
    {
        $apprenant = $user->apprenant;

        if ($apprenant === null) {
            return;
        }

        $chemins = array_filter([$apprenant->cv_path, $apprenant->candidatureSpontanee?->cv_path]);

        if ($chemins !== []) {
            Storage::disk('local')->delete($chemins);
        }
    }
}
