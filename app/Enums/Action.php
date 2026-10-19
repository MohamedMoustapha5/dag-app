<?php

namespace App\Enums;

/**
 * The elementary actions referenced by the access matrix in section 3.2 of
 * the cahier des charges (« voir, créer, modifier, supprimer, valider,
 * télécharger »).
 */
enum Action: string
{
    case Voir = 'VOIR';
    case Creer = 'CREER';
    case Modifier = 'MODIFIER';
    case Supprimer = 'SUPPRIMER';
    case Valider = 'VALIDER';
    case Telecharger = 'TELECHARGER';
}
