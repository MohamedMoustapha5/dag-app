<?php

namespace App\Policies;

use App\Models\Candidature;
use App\Models\User;

class CandidaturePolicy
{
    /**
     * Determine whether the user can view/update the model — the entreprise
     * that owns the offre this candidature was submitted to.
     */
    public function view(User $user, Candidature $candidature): bool
    {
        return $user->client?->entreprisePartenaire?->id === $candidature->offre->entreprise_id;
    }

    public function update(User $user, Candidature $candidature): bool
    {
        return $this->view($user, $candidature);
    }
}
