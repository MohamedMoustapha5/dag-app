<?php

namespace App\Support;

use App\Enums\Donnee;
use App\Enums\MembreEquipeRole;
use App\Enums\NiveauDroit;

/**
 * The DAG team access matrix, transcribed verbatim from section 3.2 of the
 * cahier des charges. This is the single source of truth: every policy or
 * middleware that gates a `membre_equipe` action reads it from here instead
 * of re-declaring the rule.
 */
class DroitsMatrix
{
    /**
     * @var array<string, array<string, NiveauDroit>>
     */
    private static array $matrice = [
        MembreEquipeRole::Promotrice->value => [
            Donnee::Catalogue->value => NiveauDroit::Total,
            Donnee::Formations->value => NiveauDroit::Total,
            Donnee::DemandesClients->value => NiveauDroit::Total,
            Donnee::Abonnements->value => NiveauDroit::Total,
            Donnee::FacturesPaiements->value => NiveauDroit::Total,
            Donnee::ContratsClauses->value => NiveauDroit::Total,
            Donnee::DossiersApprenants->value => NiveauDroit::Total,
            Donnee::EntreprisesOffres->value => NiveauDroit::Total,
            Donnee::Statistiques->value => NiveauDroit::Total,
        ],
        MembreEquipeRole::Juriste->value => [
            Donnee::Catalogue->value => NiveauDroit::Lecture,
            Donnee::Formations->value => NiveauDroit::Lecture,
            Donnee::DemandesClients->value => NiveauDroit::Lecture,
            Donnee::Abonnements->value => NiveauDroit::Lecture,
            Donnee::FacturesPaiements->value => NiveauDroit::Aucun,
            Donnee::ContratsClauses->value => NiveauDroit::Total,
            Donnee::DossiersApprenants->value => NiveauDroit::Lecture,
            Donnee::EntreprisesOffres->value => NiveauDroit::Lecture,
            Donnee::Statistiques->value => NiveauDroit::Lecture,
        ],
        MembreEquipeRole::CommunicationMarketing->value => [
            Donnee::Catalogue->value => NiveauDroit::Gestion,
            Donnee::Formations->value => NiveauDroit::Gestion,
            Donnee::DemandesClients->value => NiveauDroit::Gestion,
            Donnee::Abonnements->value => NiveauDroit::Gestion,
            Donnee::FacturesPaiements->value => NiveauDroit::Aucun,
            Donnee::ContratsClauses->value => NiveauDroit::Lecture,
            Donnee::DossiersApprenants->value => NiveauDroit::Gestion,
            Donnee::EntreprisesOffres->value => NiveauDroit::Gestion,
            Donnee::Statistiques->value => NiveauDroit::Lecture,
        ],
        MembreEquipeRole::ControleGestion->value => [
            Donnee::Catalogue->value => NiveauDroit::Lecture,
            Donnee::Formations->value => NiveauDroit::Lecture,
            Donnee::DemandesClients->value => NiveauDroit::Lecture,
            Donnee::Abonnements->value => NiveauDroit::Gestion,
            Donnee::FacturesPaiements->value => NiveauDroit::Total,
            Donnee::ContratsClauses->value => NiveauDroit::Lecture,
            Donnee::DossiersApprenants->value => NiveauDroit::Aucun,
            Donnee::EntreprisesOffres->value => NiveauDroit::Aucun,
            Donnee::Statistiques->value => NiveauDroit::Total,
        ],
        MembreEquipeRole::CreationContenu->value => [
            Donnee::Catalogue->value => NiveauDroit::Gestion,
            Donnee::Formations->value => NiveauDroit::Gestion,
            Donnee::DemandesClients->value => NiveauDroit::Aucun,
            Donnee::Abonnements->value => NiveauDroit::Aucun,
            Donnee::FacturesPaiements->value => NiveauDroit::Aucun,
            Donnee::ContratsClauses->value => NiveauDroit::Aucun,
            Donnee::DossiersApprenants->value => NiveauDroit::Aucun,
            Donnee::EntreprisesOffres->value => NiveauDroit::Aucun,
            Donnee::Statistiques->value => NiveauDroit::Aucun,
        ],
    ];

    public static function niveau(MembreEquipeRole $role, Donnee $donnee): NiveauDroit
    {
        return self::$matrice[$role->value][$donnee->value];
    }

    /**
     * @return array<string, NiveauDroit>
     */
    public static function pourRole(MembreEquipeRole $role): array
    {
        return self::$matrice[$role->value];
    }

    /**
     * A one-line French summary of who can see/manage this domain and at
     * what level, e.g. "Visible par : Directrice (total), Responsable
     * Marketing & Création de contenu (gestion), Juriste & Secrétaire
     * (lecture)." — used as a transparent droits reminder on the admin
     * pages gated by that domain, grouping roles by access level in
     * Total > Gestion > Validation > Lecture order and skipping Aucun.
     */
    public static function resume(Donnee $donnee): string
    {
        $rolesParNiveau = [];

        foreach (MembreEquipeRole::cases() as $role) {
            $niveau = self::niveau($role, $donnee);

            if ($niveau === NiveauDroit::Aucun) {
                continue;
            }

            $rolesParNiveau[$niveau->value][] = $role->label();
        }

        $segments = [];

        foreach ([NiveauDroit::Total, NiveauDroit::Gestion, NiveauDroit::Validation, NiveauDroit::Lecture] as $niveau) {
            $roles = $rolesParNiveau[$niveau->value] ?? [];

            if ($roles === []) {
                continue;
            }

            $dernier = array_pop($roles);
            $libelles = $roles === [] ? $dernier : implode(', ', $roles).' & '.$dernier;

            $segments[] = "{$libelles} (".mb_strtolower($niveau->label()).')';
        }

        return 'Visible par : '.implode(', ', $segments).'.';
    }
}
