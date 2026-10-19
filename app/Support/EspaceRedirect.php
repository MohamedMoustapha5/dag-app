<?php

namespace App\Support;

use App\Enums\UserType;
use App\Models\User;

/**
 * Resolves which authenticated space a user lands on after login or
 * registration, based on their account type (section 3.1 of the cahier
 * des charges — a fixed account type, not a team to switch between).
 */
class EspaceRedirect
{
    public static function pathFor(User $user): string
    {
        return match ($user->type) {
            UserType::Client => route('espace-client.dashboard', absolute: false),
            UserType::Apprenant => route('espace-apprenant.dashboard', absolute: false),
            UserType::MembreEquipe => route('espace-admin.dashboard', absolute: false),
            default => route('home', absolute: false),
        };
    }
}
