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
            // Case name kept as Promotrice (cahier des charges §3.2, access
            // matrix key) — only the public-facing title changed.
            self::Promotrice => 'Directrice',
            self::Juriste => 'Juriste',
            // Case name kept as CommunicationMarketing (same cahier des
            // charges key) — only the public-facing title changed.
            self::CommunicationMarketing => 'Responsable Marketing',
            // Case name kept as ControleGestion (same cahier des charges
            // key) — only the public-facing title changed.
            self::ControleGestion => 'Secrétaire',
            self::CreationContenu => 'Création de contenu',
        };
    }
}
