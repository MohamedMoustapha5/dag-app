<?php

namespace App\Notifications;

use App\Models\ClientCollaborateur;
use Illuminate\Support\Facades\URL;

/**
 * Invites a colleague into the espace of an Entreprise or Organisation: the
 * link (valid 7 days, used once) lets them choose their password and enter.
 */
class InvitationCollaborateurNotification extends NotificationDag
{
    public const VALIDITE_JOURS = 7;

    public function __construct(private ClientCollaborateur $collaborateur)
    {
        parent::__construct();
    }

    public function evenement(): string
    {
        return 'collaborateur.invitation';
    }

    public function titre(): string
    {
        return "Invitation à gérer l'espace DAG de {$this->collaborateur->client->nom}";
    }

    public function message(): string
    {
        $invitant = $this->collaborateur->invitePar?->name ?? 'Le titulaire du compte';

        return "{$invitant} vous donne accès à l'espace DAG de {$this->collaborateur->client->nom} : demandes, devis, paiements, contrats et documents.";
    }

    protected function lignes(): array
    {
        return ['Choisissez votre mot de passe pour activer votre accès. Ce lien est valable '.self::VALIDITE_JOURS.' jours.'];
    }

    /**
     * Relative signature: the link stays valid whichever address of the
     * platform the colleague opens it on.
     */
    public function url(object $notifiable): ?string
    {
        return url(URL::temporarySignedRoute(
            'collaborateurs.invitation',
            now()->addDays(self::VALIDITE_JOURS),
            ['collaborateur' => $this->collaborateur->id],
            absolute: false,
        ));
    }

    public function libelleAction(): string
    {
        return 'Activer mon accès';
    }
}
