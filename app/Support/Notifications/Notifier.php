<?php

namespace App\Support\Notifications;

use App\Enums\Action;
use App\Enums\Donnee;
use App\Models\Contrat;
use App\Models\Demande;
use App\Models\EntreprisePartenaire;
use App\Models\Facture;
use App\Models\MembreEquipe;
use App\Models\User;
use App\Notifications\AlerteEquipeNotification;
use App\Notifications\NotificationDag;
use Throwable;

/**
 * Single entry point to send a notification. A failure while sending (mail
 * server down, bad address…) is reported but never breaks the action that
 * triggered it: the payment, contract or request is already done.
 */
class Notifier
{
    public static function envoyer(User|MembreEquipe|EntreprisePartenaire|null $destinataire, NotificationDag $notification): void
    {
        if ($destinataire === null) {
            return;
        }

        try {
            $destinataire->notify($notification);
        } catch (Throwable $erreur) {
            report($erreur);
        }
    }

    /**
     * Alerts the active team members whose role may act on a domain.
     */
    public static function alerterEquipe(
        Donnee $donnee,
        Action $action,
        string $evenement,
        string $titre,
        string $message,
        ?string $url = null,
    ): void {
        MembreEquipe::query()
            ->where('statut', 'ACTIF')
            ->get()
            ->filter(fn (MembreEquipe $membre) => $membre->peut($donnee, $action))
            ->each(fn (MembreEquipe $membre) => self::envoyer(
                $membre,
                new AlerteEquipeNotification($evenement, $titre, $message, $url),
            ));
    }

    /**
     * The account (client or apprenant) a demande, contrat or facture belongs
     * to; for a partner entreprise, the account linked to it.
     */
    public static function utilisateurDe(Demande|Contrat|Facture $enregistrement): ?User
    {
        if ($enregistrement->getAttribute('client_id') !== null) {
            return $enregistrement->client->user;
        }

        if (! $enregistrement instanceof Demande && $enregistrement->apprenant_id !== null) {
            return $enregistrement->apprenant->user;
        }

        if ($enregistrement->getAttribute('entreprise_id') !== null) {
            $entreprise = $enregistrement->entreprise;

            return $entreprise->client_id !== null ? $entreprise->client->user : null;
        }

        return null;
    }
}
