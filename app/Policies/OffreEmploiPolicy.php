<?php

namespace App\Policies;

use App\Models\OffreEmploi;
use App\Models\User;

class OffreEmploiPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, OffreEmploi $offre): bool
    {
        return $user->client?->entreprisePartenaire?->id === $offre->entreprise_id;
    }
}
