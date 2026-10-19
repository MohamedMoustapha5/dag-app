# Phase 1 — Fondations : base de données, modèles, données de démo

Statut : **terminée**. Correspond au critère « l'application a une base de données
qui reflète fidèlement le cahier des charges et le diagramme de classes, avec des
données de démonstration cohérentes avec les maquettes ».

## 1. Ce qui existait avant cette phase

Le dépôt contenait déjà un premier jet de migrations (`2026_10_16_025828_...` à
`...025846_...`) et un starter kit Laravel (Fortify, Teams, Passkeys, 2FA). Ce premier
jet avait plusieurs problèmes bloquants, corrigés dans cette phase :

- `inscriptions.session_id` référençait la table **`sessions`** (table interne de
  Laravel pour le stockage des sessions HTTP, clé primaire `string`), au lieu d'une
  table de sessions de formation. La table `formation_sessions` existait mais était
  vide (juste `id` + timestamps).
- `notifications` entrait en collision avec la table native Laravel utilisée par le
  trait `Notifiable`.
- Rôles de l'équipe DAG incomplets/incorrects par rapport à la matrice 3.2 du cahier
  des charges (rôle « Création de contenu » manquant, « Communication » et
  « Marketing digital » scindés en deux valeurs au lieu d'un seul rôle).
- Beaucoup de champs du cahier des charges absents (ex. `entreprises_partenaires`
  sans compte utilisateur alors que la section 6.6 exige que l'entreprise se
  connecte ; pas de suivi de présence/séances ; pas de bibliothèque de clauses ; pas
  de journal d'activité ; pas de distinction client/entreprise sur les demandes,
  abonnements et factures alors que les maquettes `*-entreprise.html` montrent que
  les entreprises partenaires ont aussi des demandes, abonnements et factures DAG).
- MySQL n'était pas démarré sur la machine (service Windows arrêté, droits
  insuffisants pour le démarrer via le service manager) — démarré manuellement en
  pointant `mysqld.exe` vers son propre `my.ini` (port 3306, base `app_dag`).
- **Bug découvert dans le squelette de tests** : `tests/Pest.php` avait
  `RefreshDatabase` commenté, donc _tous_ les tests existants (91 tests) échouaient
  avec « no such table ». Corrigé — les 91 tests du starter kit passent maintenant.

## 2. Schéma final

22 migrations ajoutées (`2026_10_16_100001` à `...100024`), en plus des migrations du
starter kit laissées intactes.

### Identité

- `users` + colonnes `type` (`CLIENT`, `APPRENANT`, `MEMBRE_EQUIPE` — voir mise à
  jour phase 2 : `ENTREPRISE_PARTENAIRE` a été retiré, ce n'est plus un type de
  compte) et `locale` (prêt pour le FR/EN du cahier des charges).
- `clients` (type particulier/entreprise/organisation, statut de validation)
- `apprenants` (CV, consentement RGPD-like pour la conservation du CV — section 7)
- `membres_equipe` (rôle parmi les 5 de la matrice 3.2, statut actif/inactif)
- `entreprises_partenaires` (fiche métier gérée par l'admin, statut en attente/
  actif/inactif — sans compte `User` associé)

### Catalogue & formules

- `services` (catégorie, fournisseur `DAG`/`Nova-Tech`, statut brouillon/publié)
- `formules` (contenu, services inclus en JSON, badge « populaire »)

### Formation (section 6.5)

- `formations`, `formation_sessions` (ville, mode, lieu, formateur, places, statut),
  `seances` (une par date de session, pour le suivi de présence),
  `inscriptions` (liste d'attente si session complète), `presences`
  (présence par séance et par inscription), `attestations` (référence + PDF)

### Placement (section 6.6)

- `offres_emploi` (type de contrat, date limite → disparition automatique du
  catalogue public via le scope `publiees()`), `candidatures` (unique par
  apprenant + offre pour bloquer les doublons, cf. cas d'erreur du cahier des
  charges)

### Commercial

- `demandes` (client **ou** entreprise, service ou formule, suivi Nova-Tech séparé
  via `avancement_partenaire`), `abonnements` (client **ou** entreprise)

### Contrats (section 6.7)

- `modeles_contrat` (4 types : prestation de service, abonnement, convention de
  stage, contrat partenaire), `clauses` (bibliothèque éditable par le Juriste),
  `contrats` (référence + code de vérification générés automatiquement, photo de
  signature, IP et date d'import comme preuve, contestation/invalidation de
  signature, relance automatique)

### Facturation (section 6.4)

- `factures` (référence auto, montant payé partiel, type service/abonnement/
  formation), `paiements` (justificatif pour paiement manuel, membre de l'équipe
  qui l'a validé)

### Support

- `notification_logs` (canal email/whatsapp/sms, prêt pour le multicanal du
  cahier des charges sans refonte), `activity_logs` (journal d'action, section 6.9)

## 3. Modèles Eloquent

22 modèles dans `app/Models/`, clés primaires UUID (`HasUuids`), relations typées
(`BelongsTo`/`HasMany`/`HasOne`), casts (`decimal:2`, `date`, `datetime`, `boolean`,
enum PHP pour `membres_equipe.role`). Deux enums ajoutés dans `app/Enums/` :
`UserType` et `MembreEquipeRole`.

Génération automatique des références humaines (`DAG-DEM-2026-0001`,
`DAG-CTR-2026-0001`, `DAG-F-2026-0001`, `DAG-ATT-2026-0001`) via un trait réutilisable
`App\Concerns\GeneratesReference`, et du code de vérification des contrats
(`WVK-287N`-style) directement dans `Contrat`.

## 4. Données de démonstration

`php artisan db:seed` (ou `migrate:fresh --seed`) peuple :

- 5 membres de l'équipe DAG (Marie D. Promotrice, Fatou M. Juriste, Jules K.
  Communication & marketing digital, Hervé N. Contrôle de gestion, Rosine T.
  Création de contenu) — mot de passe `password` pour tous les comptes de démo.
- 17 services (8 DAG + 9 Nova-Tech) et 3 formules (Essentiel, Croissance, Premium)
  conformes aux sections 5.1/5.2/6.3 du cahier des charges.
- 4 formations (3 ouvertes/complète + 1 terminée), avec séances et présences pour la
  session Community Management (11 → 10 inscrits, 2 en attente de paiement).
- 3 entreprises partenaires (Kiro Digital Solutions actif, Atlas Corp actif, Rosine
  Shop inactif) et leurs offres d'emploi.
- 2 clients (Aïcha N. actif, Fatou Boutique en attente) et 10 apprenants.
- Un parcours complet pour Aïcha N. : demande → devis → contrat envoyé → facture
  payée, plus un abonnement actif signé avec historique de facturation, pour que les
  futurs écrans « espace client » aient des données réalistes dès la phase 3-4.
- Un parcours entreprise pour Kiro Digital Solutions : contrat-cadre partenaire
  signé, abonnement actif, facture payée.
- 3 candidatures (Stéphanie A. en étude, Brice K. retenue avec convention de stage
  signée, Noura E. refusée) — correspond exactement au scénario de
  `offre-detail.html`.
- Bibliothèque de 5 clauses et 4 modèles de contrat.
- Quelques entrées de journal d'activité et de notifications envoyées.

Comptes de démo utiles pour la suite (mot de passe `password`) :

| Rôle                     | E-mail               |
| ------------------------ | -------------------- |
| Promotrice (accès total) | marie.d@dag.cm       |
| Juriste                  | fatou.m@dag.cm       |
| Client actif             | aicha.n@exemple.com  |
| Apprenante               | stephanie.a@mail.com |

_(Kiro Digital Solutions reste une entreprise partenaire de démo, mais n'a plus
de compte propre — voir la mise à jour en tête de `docs/02-phase-2-...md`.)_

## 5. Tests écrits (Pest, phase 1)

`tests/Feature/Models/ReferenceGenerationTest.php` et `DomainModelsTest.php` :

- Génération séquentielle des références (`DAG-DEM-2026-0001`, `0002`, …) et
  non-écrasement d'une référence fournie explicitement.
- Génération de la référence et du code de vérification d'un contrat.
- Clé primaire UUID valide sur un modèle du domaine.
- Relation `User → Client` dans les deux sens.
- Cast de `membres_equipe.role` vers l'enum `MembreEquipeRole` et son libellé FR.
- Calcul des places restantes d'une session de formation.
- Contrainte unique (apprenant, offre) → double candidature rejetée en base.
- Scope `OffreEmploi::publiees()` qui exclut les offres dont la date limite est
  dépassée.

## 6. Comment tester cette phase vous-même

```bash
# 1. S'assurer que MySQL tourne et que .env pointe vers app_dag (déjà configuré)
php artisan migrate:fresh --seed

# 2. Vérifier les comptes de démo
php artisan tinker --execute='echo \App\Models\Client::count() . " clients, " . \App\Models\Service::count() . " services";'

# 3. Lancer la suite de tests (sqlite en mémoire, ne touche pas à app_dag)
php artisan test
```

## 7. Limites connues / à traiter plus tard

- La génération de référence (`count() + 1`) n'est pas protégée contre une
  concurrence élevée — acceptable pour une application d'agence à faible volume
  simultané ; à revoir si le trafic augmente significativement.
- Le renommage de formule (historique de renégociation) n'est pas encore modélisé
  en base — seul le montant courant de l'abonnement est suivi. À ajouter si DAG a
  besoin d'un historique détaillé des renégociations.
- Le choix de l'agrégateur de paiement (Orange Money / MTN MoMo / carte) reste une
  décision business en attente (section 14 du cahier des charges) ; le module de
  paiement (phase 5) sera construit derrière une interface indépendante du
  prestataire choisi.
