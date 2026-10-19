<?php

namespace App\Enums;

enum MembreEquipeRole: string
{
    case Promotrice = 'PROMOTRICE';
    case Juriste = 'JURISTE';
    case CommunicationMarketing = 'COMMUNICATION_MARKETING';
    case ControleGestion = 'CONTROLE_GESTION';
    case CreationContenu = 'CREATION_CONTENU';

    public function label(): string
    {
        return match ($this) {
            self::Promotrice => 'Promotrice',
            self::Juriste => 'Juriste',
            self::CommunicationMarketing => 'Communication & marketing digital',
            self::ControleGestion => 'Contrôle de gestion',
            self::CreationContenu => 'Création de contenu',
        };
    }
}
