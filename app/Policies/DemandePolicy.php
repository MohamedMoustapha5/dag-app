<?php

namespace App\Policies;

use App\Models\Demande;
use App\Models\User;

class DemandePolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Demande $demande): bool
    {
        return $user->client !== null && $demande->client_id === $user->client->id;
    }
}
