# DAG — Analyse du projet et plan de développement

Ce document résume l'analyse du cahier des charges (v4.2), des maquettes HTML/CSS/JS et
des 5 diagrammes UML fournis, et fixe le plan de développement suivi phase par phase.
Il sert de référence pendant tout le projet — à mettre à jour si une décision change.

## 1. Objectif et périmètre

Plateforme web DAG (Digital Agency Group) : catalogue de services, formations avec
placement en entreprise, paiement en ligne (Orange Money / MTN MoMo / carte), contrats
signés par photo de signature importée (pas de signature électronique au sens strict),
et quatre espaces utilisateurs distincts : **Client**, **Apprenant**, **Entreprise
partenaire**, **Équipe DAG** (6 rôles internes). Nova-Tech est un partenaire externe
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

6 rôles internes (`App\Enums\MembreEquipeRole`) : Promotrice (accès total), Juriste,
Communication & marketing digital, Contrôle de gestion, Création de contenu et
Développeur (lecture seule par défaut, relevable depuis la page Équipe). La
matrice de droits par type de donnée est implémentée depuis la phase 2
(`App\Support\DroitsMatrix`, détail dans `docs/02-phase-2-authentification-roles.md`).

## 6. Plan de développement par phase

### Avancement

| Terminées | Partielles | À venir | Total |
| --------- | ---------- | ------- | ----- |
| 6         | 4          | 1       | 11    |

Légende : `[x]` fait, `[ ]` reste à faire. Une phase n'est cochée que lorsque tous ses
points le sont. Chaque phase livrée a son fichier de documentation dans `docs/`.

### Phase 1 — Base de données — terminée

Documentation : [`01-phase-1-fondations-donnees.md`](01-phase-1-fondations-donnees.md)

- [x] Migrations, modèles et enums
- [x] Seeders de démonstration
- [x] Tests

### Phase 2 — Authentification et rôles — terminée

Documentation : [`02-phase-2-authentification-roles.md`](02-phase-2-authentification-roles.md)

- [x] Authentification par type de compte (client, apprenant)
- [x] Rôles internes et matrice de droits
- [x] Espaces de base

### Phase 3 — Espace public — terminée

Documentation : [`04-phase-3-espace-public.md`](04-phase-3-espace-public.md)
(charte graphique : [`03-charte-graphique-et-assets.md`](03-charte-graphique-et-assets.md))

- [x] Catalogue de services et formules
- [x] Formations et candidature spontanée
- [x] Contact et audit gratuit

### Phase 4 — Espace client — terminée

Documentation : [`05-phase-4-espace-client.md`](05-phase-4-espace-client.md)

- [x] Demandes et suivi
- [x] Abonnements, factures, contrats
- [x] Documents

### Phase 5 — Paiement et facturation — partielle (passerelle simulée)

Documentation : [`06-phase-5-paiement-facturation.md`](06-phase-5-paiement-facturation.md)
et [`12-services-externes-a-brancher.md`](12-services-externes-a-brancher.md) (comptes et clés à obtenir)

- [x] Facture émise avec le devis
- [x] Paiement en ligne : Orange Money, MTN MoMo, carte
- [x] Webhook signé du prestataire
- [x] Paiement manuel et confirmation par l'administration
- [x] PDF de la facture payée (espaces client et apprenant)
- [ ] Prestataire de paiement réel : compte marchand, clés API et adaptateur (le parcours
  complet fonctionne en attendant avec la passerelle de simulation)

### Phase 6 — Contrats et signature — partielle

Documentation : [`07-phase-6-contrats.md`](07-phase-6-contrats.md)

- [x] Contrats, modèles et clauses
- [x] Création d'un contrat depuis une demande payée
- [x] Rédaction automatique du contrat (modèle actif et clauses)
- [x] Envoi au client et signature par photo importée
- [x] PDF du contrat signé (administration et client)
- [x] Relance automatique des contrats non signés (voir la phase 10)
- [ ] Contestation de signature

### Phase 7 — Module Formation — terminée

Documentation : [`08-phase-7-formation.md`](08-phase-7-formation.md)

- [x] Formations, sessions et séances
- [x] Inscriptions, validation et export
- [x] Attestations validées par DAG, avec PDF
- [x] Espace apprenant : progression, dossier, CV
- [x] Pointage de la présence par l'équipe (Présent, En retard, Absent, Excusé), assiduité et export

### Phase 8 — Entreprises partenaires et placement — terminée

Documentation : [`09-phase-8-entreprises-placement.md`](09-phase-8-entreprises-placement.md)

- [x] Validation des entreprises, publication et prolongation des offres
- [x] Candidature déposée par DAG pour un apprenant
- [x] Dossiers reçus de candidats externes
- [x] Sélection ou refus par l'entreprise

### Phase 9 — Espace administrateur — terminée

Documentation : [`10-phase-9-espace-administrateur.md`](10-phase-9-espace-administrateur.md)

- [x] Connexion séparée des comptes équipe
- [x] Équipe et droits modifiables par rôle
- [x] Tableau de bord et statistiques adaptés au rôle
- [x] Catalogue, grille tarifaire, abonnements
- [x] Comptes clients et apprenants, dossiers
- [x] Journal d'activité (réservé à la Directrice)
- [x] Centre de documents : contrats signés, attestations et CV au même endroit

### Phase 10 — Notifications — partielle (e-mail actif, SMS et WhatsApp prêts mais inactifs)

Documentation : [`11-phase-10-notifications.md`](11-phase-10-notifications.md)
et [`12-services-externes-a-brancher.md`](12-services-externes-a-brancher.md) (comptes et clés à obtenir)

- [x] E-mails (devis, paiement, contrat, inscription, attestation, candidature)
- [x] Alertes de l'équipe (nouvelle demande, paiement reçu, contrat signé)
- [x] Rappels de séance et relances de contrats non signés
- [x] Architecture prête pour WhatsApp et SMS (canaux, fournisseur, journal)
- [x] Suivi des envois dans Statistiques
- [ ] E-mail professionnel : serveur d'envoi (SMTP), SPF, DKIM et DMARC du domaine
- [ ] Adresses réelles des membres de l'équipe
- [ ] SMS réels : fournisseur, nom d'expéditeur validé, adaptateur
- [ ] WhatsApp réel : compte Business vérifié, modèles de message approuvés, adaptateur
- [ ] Consentement par canal (SMS, WhatsApp) et normalisation des numéros

### Phase 11 — Déploiement et recette finale — à venir

- [ ] Configuration de production
- [ ] Recette finale

> La phase « Demandes partenaires Nova-Tech » a été annulée : elle ne figure plus au plan.

Chaque phase est développée, testée par l'IA (tests Pest + vérifications manuelles),
documentée dans `docs/`, puis remise à l'utilisateur pour test et validation avant de
passer à la phase suivante.
