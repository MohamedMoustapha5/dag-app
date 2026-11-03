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

Légende : `[x]` phase terminée et documentée, `[ ]` phase à faire ou partielle
(le reste à faire est indiqué).

- [x] **Phase 1 — Base de données** : migrations, modèles, enums, seeders de démo, tests.
  Documentation : [`01-phase-1-fondations-donnees.md`](01-phase-1-fondations-donnees.md)
- [x] **Phase 2 — Authentification et rôles** : authentification par type de compte, rôles,
  matrice de droits, espaces de base.
  Documentation : [`02-phase-2-authentification-roles.md`](02-phase-2-authentification-roles.md)
- [x] **Phase 3 — Espace public** : catalogue, formations, formules, contact, audit gratuit.
  Documentation : [`04-phase-3-espace-public.md`](04-phase-3-espace-public.md)
  (charte graphique : [`03-charte-graphique-et-assets.md`](03-charte-graphique-et-assets.md))
- [x] **Phase 4 — Espace client** : demandes, suivi, documents.
  Documentation : [`05-phase-4-espace-client.md`](05-phase-4-espace-client.md)
- [x] **Phase 5 — Paiement et facturation** : Orange Money, MTN MoMo, carte, facture
  manuelle. Parcours complet avec passerelle simulée ; il reste à brancher un prestataire
  réel (compte et clés API).
  Documentation : [`06-phase-5-paiement-facturation.md`](06-phase-5-paiement-facturation.md)
- [ ] **Phase 6 — Contrats et signature** : partielle. Gestion des contrats, modèles,
  clauses et PDF signé en place ; reste la signature par photo importée côté client.
  Documentation : [`07-phase-6-contrats.md`](07-phase-6-contrats.md)
- [ ] **Phase 7 — Module Formation** : partielle. Sessions, inscriptions et attestations
  validées en place ; reste le pointage de la présence par l'équipe.
  Documentation : [`08-phase-7-formation.md`](08-phase-7-formation.md)
- [x] **Phase 8 — Entreprises partenaires et placement** : offres, candidatures, dossiers
  reçus de candidats externes.
  Documentation : [`09-phase-8-entreprises-placement.md`](09-phase-8-entreprises-placement.md)
- [ ] **Phase 9 — Demandes partenaires Nova-Tech** : volontairement exclue pour le moment.
- [ ] **Phase 10 — Espace administrateur** : partielle. Catalogue, équipe et droits,
  tableau de bord, comptes et documents en place ; reste le journal d'activité.
  Documentation : [`10-phase-10-espace-administrateur.md`](10-phase-10-espace-administrateur.md)
- [ ] **Phase 11 — Notifications** : e-mail d'abord, architecture prête pour WhatsApp et SMS.
- [ ] **Phase 12 — Déploiement et recette finale.**

Chaque phase terminée ou partielle a son fichier de documentation dans `docs/`.

Chaque phase est développée, testée par l'IA (tests Pest + vérifications manuelles),
documentée dans `docs/`, puis remise à l'utilisateur pour test et validation avant de
passer à la phase suivante.
