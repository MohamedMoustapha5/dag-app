<?php

namespace App\Enums;

/**
 * The five access levels defined in section 3.2 of the cahier des charges.
 */
enum NiveauDroit: string
{
    case Total = 'TOTAL';
    case Gestion = 'GESTION';
    case Validation = 'VALIDATION';
    case Lecture = 'LECTURE';
    case Aucun = 'AUCUN';

    public function label(): string
    {
        return match ($this) {
            self::Total => 'Total',
            self::Gestion => 'Gestion',
            self::Validation => 'Validation',
            self::Lecture => 'Lecture',
            self::Aucun => 'Aucun',
        };
    }

    /**
     * Whether this level grants the given action, per the definitions in
     * section 3.2: Total (voir, créer, modifier, supprimer, valider,
     * télécharger), Gestion (voir, créer, modifier), Validation (voir,
     * valider), Lecture (voir et télécharger uniquement), Aucun (rien).
     */
    public function autorise(Action $action): bool
    {
        return match ($this) {
            self::Total => true,
            self::Gestion => in_array($action, [Action::Voir, Action::Creer, Action::Modifier], true),
            self::Validation => in_array($action, [Action::Voir, Action::Valider], true),
            self::Lecture => in_array($action, [Action::Voir, Action::Telecharger], true),
            self::Aucun => false,
        };
    }
}
