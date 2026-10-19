<?php

namespace App\Enums;

/**
 * The data domains listed as rows in the access matrix, section 3.2 of the
 * cahier des charges.
 */
enum Donnee: string
{
    case Catalogue = 'CATALOGUE';
    case Formations = 'FORMATIONS';
    case DemandesClients = 'DEMANDES_CLIENTS';
    case Abonnements = 'ABONNEMENTS';
    case FacturesPaiements = 'FACTURES_PAIEMENTS';
    case ContratsClauses = 'CONTRATS_CLAUSES';
    case DossiersApprenants = 'DOSSIERS_APPRENANTS';
    case EntreprisesOffres = 'ENTREPRISES_OFFRES';
    case Statistiques = 'STATISTIQUES';

    public function label(): string
    {
        return match ($this) {
            self::Catalogue => 'Catalogue de services',
            self::Formations => 'Formations & sessions',
            self::DemandesClients => 'Demandes clients',
            self::Abonnements => 'Abonnements',
            self::FacturesPaiements => 'Factures & paiements',
            self::ContratsClauses => 'Contrats & clauses',
            self::DossiersApprenants => 'Dossiers apprenants',
            self::EntreprisesOffres => 'Entreprises partenaires & offres',
            self::Statistiques => 'Statistiques',
        };
    }
}
