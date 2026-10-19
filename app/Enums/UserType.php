<?php

namespace App\Enums;

enum UserType: string
{
    case Client = 'CLIENT';
    case Apprenant = 'APPRENANT';
    case MembreEquipe = 'MEMBRE_EQUIPE';

    public function label(): string
    {
        return match ($this) {
            self::Client => 'Client',
            self::Apprenant => 'Apprenant',
            self::MembreEquipe => 'Membre de l\'équipe DAG',
        };
    }
}
