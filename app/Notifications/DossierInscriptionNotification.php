<?php

namespace App\Notifications;

use App\Models\Inscription;

/**
 * Tells the apprenant how DAG reviewed their enrolment dossier: validated
 * (the registration fee can now be paid) or to be completed (with the
 * team's reason).
 */
class DossierInscriptionNotification extends NotificationDag
{
    public function __construct(private Inscription $inscription, private bool $valide)
    {
        parent::__construct();
    }

    public function evenement(): string
    {
        return $this->valide ? 'inscription.dossier_valide' : 'inscription.dossier_a_completer';
    }

    public function preference(): ?string
    {
        return 'notif_seances';
    }

    public function titre(): string
    {
        return $this->valide
            ? "Dossier validé : réglez vos frais d'inscription"
            : "Votre dossier d'inscription est à compléter";
    }

    public function message(): string
    {
        $formation = $this->inscription->formation->nom;

        if (! $this->valide) {
            return "L'équipe DAG a examiné votre dossier pour la formation « {$formation} » : quelques pièces sont à revoir.";
        }

        $montant = number_format((float) $this->inscription->montant_negocie, 0, ',', ' ');

        return "Votre dossier pour la formation « {$formation} » est validé. Réglez les frais d'inscription ({$montant} FCFA) pour confirmer votre place.";
    }

    protected function lignes(): array
    {
        if (! $this->valide) {
            return array_values(array_filter([
                $this->inscription->motif_decision,
                'Déposez les pièces corrigées depuis vos inscriptions : votre place reste réservée pendant ce temps.',
            ]));
        }

        return ['Paiement en ligne depuis vos factures : Orange Money, MTN Mobile Money ou carte.'];
    }

    public function url(object $notifiable): ?string
    {
        return $this->valide
            ? route('espace-apprenant.factures')
            : route('espace-apprenant.inscriptions');
    }

    public function libelleAction(): string
    {
        return $this->valide ? 'Régler les frais' : 'Compléter mon dossier';
    }
}
