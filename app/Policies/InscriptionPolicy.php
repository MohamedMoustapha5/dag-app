<?php

namespace App\Policies;

use App\Models\Inscription;
use App\Models\User;

class InscriptionPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Inscription $inscription): bool
    {
        return $user->apprenant !== null && $inscription->apprenant_id === $user->apprenant->id;
    }
}
