# DAG — Analyse du projet et plan de développement

Ce document résume l'analyse du cahier des charges (v4.2), des maquettes HTML/CSS/JS et
des 5 diagrammes UML fournis, et fixe le plan de développement suivi phase par phase.
Il sert de référence pendant tout le projet — à mettre à jour si une décision change.

## 1. Objectif et périmètre

Plateforme web DAG (Digital Agency Group) : catalogue de services, formations avec
placement en entreprise, paiement en ligne (Orange Money / MTN MoMo / carte), contrats
signés par photo de signature importée (pas de signature électronique au sens strict),
et quatre espaces utilisateurs distincts : **Client**, **Apprenant**, **Entreprise
partenaire**, **Équipe DAG** (5 rôles internes). Nova-Tech est un partenaire externe
sans accès à l'application : les demandes qui le concernent sont juste transmises par
message préparé (e-mail/WhatsApp).

## 2. Stack technique

- Laravel 13 + Fortify (auth, 2FA, passkeys) — déjà scaffoldé dans le dépôt
- Inertia.js v3 + React (frontend)
- MySQL (dev et prod, à la demande explicite du porteur de projet — le cahier des
  charges suggérait SQLite en dev, mais l'environnement réel utilise déjà MySQL)
- Tailwind CSS, Pest pour les tests

## 3. Décisions d'architecture prises en phase 1

1. **Starter kit « Teams »** : le squelette Laravel installé est le starter kit
   officiel avec gestion d'équipes multi-tenant (`Team`, `Membership`,
   `TeamInvitation`, routes préfixées par `{current_team}`). Ce concept n'existe pas
   dans le cahier des charges DAG (qui a 4 types de comptes fixes, pas des équipes
   multi-organisation). Décision : **le code Teams est laissé en place sans y
   toucher** (non destructif), mais les espaces DAG (client, apprenant, entreprise,
   admin) seront construits avec leurs propres routes/contrôleurs, **hors** du
   préfixe `{current_team}`. Un compte DAG aura par effet de bord une « équipe
   personnelle » Jetstream vide et inutilisée — sans impact fonctionnel.
2. **Agrégateur de paiement** : non choisi à ce stade (point à valider par DAG, cf.
   section 14 du cahier des charges). Le module paiement sera développé avec une
   interface `PaymentGateway` pluggable et un adaptateur « manuel/démo » en
   attendant le choix réel d'Orange Money / MTN MoMo / carte.
3. **Table `notifications`** : le schéma brouillon réutilisait le nom de table
   `notifications`, qui entre en collision avec la table native de Laravel
   (`Notifiable`/canal database). Renommée en `notification_logs`.
4. **Journal d'activité (section 6.9)** : pas de package tiers ajouté (changement de
   dépendance non autorisé sans validation) — implémenté en interne via la table
   `activity_logs` et `ActivityLog::record()`.

## 4. Modèle de données (vue d'ensemble)

| Domaine     | Tables                                                                                             |
| ----------- | -------------------------------------------------------------------------------------------------- |
| Identité    | `users` (+ `type`, `locale`), `clients`, `apprenants`, `membres_equipe`, `entreprises_partenaires` |
| Catalogue   | `services`, `formules`                                                                             |
| Formation   | `formations`, `formation_sessions`, `seances`, `inscriptions`, `presences`, `attestations`         |
| Placement   | `offres_emploi`, `candidatures`                                                                    |
| Commercial  | `demandes`, `abonnements`                                                                          |
| Contrats    | `modeles_contrat`, `clauses`, `contrats`                                                           |
| Facturation | `factures`, `paiements`                                                                            |
| Support     | `notification_logs`, `activity_logs`                                                               |

Détail complet, relations et justification de chaque colonne : voir
`docs/01-phase-1-fondations-donnees.md`.

## 5. Rôles et droits (matrice 3.2 du cahier des charges)

5 rôles internes (`App\Enums\MembreEquipeRole`) : Promotrice (accès total), Juriste,
Communication & marketing digital, Contrôle de gestion, Création de contenu. La
matrice de droits par type de donnée est implémentée depuis la phase 2
(`App\Support\DroitsMatrix`, détail dans `docs/02-phase-2-authentification-roles.md`).

## 6. Plan de développement par phase

| Phase | Contenu                                                                        | Statut                       |
| ----- | ------------------------------------------------------------------------------ | ---------------------------- |
| 1     | Base de données : migrations, modèles, enums, seeders de démo, tests           | Terminé                      |
| 2     | Authentification par type de compte, rôles, matrice de droits, espaces de base | Terminé                      |
| 3     | Espace public : catalogue, formations, formules, contact, audit gratuit        | Terminé                      |
| 4     | Espace client : demandes, suivi, documents                                     | Terminé                      |
| 5     | Paiement et facturation (Orange Money / MTN MoMo / carte, facture manuelle)    | Terminé (passerelle simulée) |
| 6     | Contrats et signature par photo importée                                       | Partiel                      |
| 7     | Module Formation (inscription, présence, attestation)                          | Terminé                      |
| 8     | Entreprises partenaires et placement (offres, candidatures)                    | Terminé                      |
| 9     | Demandes partenaires Nova-Tech                                                 | À venir                      |
| 10    | Espace administrateur (catalogue, équipe, journal)                             | Terminé                      |
| 11    | Notifications (e-mail d'abord, architecture prête pour WhatsApp/SMS)           | À venir                      |
| 12    | Déploiement et recette finale                                                  | À venir                      |

Détail de l'avancement :

- Phases 1 à 4 : documentées dans `docs/01` à `docs/05`.
- Phase 5 : documentée dans `docs/06-phase-5-paiement-facturation.md`. Le parcours
  complet (demande, devis, paiement, facture) fonctionne avec une passerelle de
  simulation ; il reste à brancher un prestataire réel (compte et clés API).
- Phase 6 : les contrats sont créés, envoyés et consultables, et le PDF du contrat
  signé est téléchargeable par l'administrateur. La signature par photo importée
  côté client n'est pas encore en place.
- Phases 7, 8 et 10 : réalisées avec l'espace administrateur (sessions, inscriptions,
  attestations validées avant délivrance, offres, candidatures déposées par DAG,
  dossiers reçus de candidats externes, équipe et droits modifiables).
- Phase 9 (Nova-Tech) : volontairement exclue pour le moment.

Chaque phase est développée, testée par l'IA (tests Pest + vérifications manuelles),
documentée dans `docs/`, puis remise à l'utilisateur pour test et validation avant de
passer à la phase suivante.
