<?php

namespace App\Policies;

use App\Models\ProfilPropose;
use App\Models\User;

class ProfilProposePolicy
{
    public function update(User $user, ProfilPropose $profil): bool
    {
        return $user->client?->entreprisePartenaire?->id === $profil->entreprise_id;
    }
}
