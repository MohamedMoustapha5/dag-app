<?php

namespace App\Enums;

enum UserType: string
{
    case Client = 'CLIENT';
    case Apprenant = 'APPRENANT';

    public function label(): string
    {
        return match ($this) {
            self::Client => 'Client',
            self::Apprenant => 'Apprenant',
        };
    }
}
