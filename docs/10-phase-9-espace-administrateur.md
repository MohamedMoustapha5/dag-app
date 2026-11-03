# Phase 9 — Espace administrateur

Statut : **partielle**. Correspond à la ligne « Espace administrateur (catalogue,
équipe, journal) » du plan. L'espace est complet à l'exception du journal d'activité.

## 1. Authentification séparée

Les comptes de l'équipe DAG sont isolés des comptes publics.

- Table `membres_equipe` (nom, e-mail, mot de passe, rôle, statut) distincte de `users`.
- Guard `admin` et connexion dédiée sur `/espace-admin/connexion`. Un compte public ne
  peut pas se connecter à l'administration, et réciproquement.
- Un membre désactivé est déconnecté et refusé à chaque requête (`EnsureMembreActif`).
- Une migration a déplacé les comptes équipe existants sans perte d'identifiants.

## 2. Rôles et droits évolutifs

Cinq rôles : Directrice (accès total), Juriste, Responsable Marketing, Secrétaire,
Création de contenu.

- Les droits par rôle et par domaine (catalogue, formations, demandes clients,
  abonnements, factures et paiements, contrats et clauses, dossiers apprenants,
  entreprises et offres, statistiques) ont des valeurs par défaut issues du cahier des
  charges, modifiables depuis la page Équipe sans changer le code.
- Niveaux : Total, Gestion (voir, créer, modifier), Validation (voir, valider),
  Lecture (voir, télécharger), Aucun.
- Les modifications sont stockées dans `droits_roles`, mises en cache et prises en
  compte immédiatement. Les droits de la Directrice ne sont pas modifiables.
- Seule la Directrice crée les comptes, change les rôles et édite les droits ; nul ne
  peut changer son propre rôle ni se désactiver.

## 3. Tableau de bord et statistiques adaptés

Chaque indicateur n'est calculé que si le rôle a le droit de voir le domaine concerné.
Un rôle ne reçoit donc aucune donnée qu'il n'a pas le droit de voir, et l'interface
n'affiche que ce qui lui est transmis.

## 4. Pages de l'espace

| Section | Pages |
| ------- | ----- |
| Activité commerciale | Demandes clients (devis, contrat), Comptes clients et apprenants, Abonnements et formules, Factures et paiements, Contrats et clauses |
| Catalogue | Catalogue de services, Grille tarifaire |
| Formation | Formations et sessions, Attestations |
| Recrutement | Entreprises et offres, Dossiers apprenants |
| Pilotage | Statistiques |
| Administration | Équipe DAG et droits |

Les liens de la barre latérale n'apparaissent que si le rôle a au moins le droit de
voir le domaine.

## 5. Documents PDF

Les PDF d'attestation, de facture et de contrat utilisent l'identité DAG (logo,
couleurs, cachet signé de la directrice) : voir les phases 5, 6 et 7.

## 6. Reste à faire

- Journal d'activité consultable (la table `activity_logs` existe mais aucun écran ne
  l'expose).

## 7. Tests

Un fichier par domaine dans `tests/Feature/Espaces/Admin/` (dont `EquipeTest`,
`DashboardTest`, `StatistiqueTest`), `tests/Feature/Auth/LoginRedirectTest.php` pour la
connexion séparée et `tests/Feature/Support/DroitsMatrixTest.php` pour la matrice.
