# Phase 7 — Module Formation

Statut : **terminée**. Correspond à la ligne « Module Formation (inscription,
présence, attestation) » du plan. Les formations, sessions, inscriptions, le pointage
de la présence par l'équipe et les attestations sont opérationnels de bout en bout.

## 1. Réalisé

### Administration

- **Formations et sessions** : la page est centrée sur les sessions. Création rapide
  d'une session, modification, suppression, image téléversée sur le disque public.
- **Séances** : ajout et suppression des séances d'une session.
- **Inscriptions** : liste des inscrits d'une session, validation ou refus d'une
  inscription, export CSV.
- **Attestations** : voir la section 3.

### Apprenant

- Catalogue des formations, inscriptions, progression par formation (séances,
  présences, pourcentage), tableau de bord.
- Dossier personnel : coordonnées, téléversement du CV (PDF, DOC, DOCX, 5 Mo),
  consentement de partage du CV avec les entreprises.
- Factures de formation, paiement en ligne (voir `docs/06-phase-5-paiement-facturation.md`).

## 2. Données

`formations`, `formation_sessions`, `seances`, `inscriptions`, `presences`,
`attestations`.

Statuts d'inscription : `DOSSIER_EN_ETUDE`, `DOSSIER_A_COMPLETER`,
`EN_ATTENTE_PAIEMENT`, `VALIDEE`, `FORMEE`, `ABANDONNEE`.

Statuts de présence : `PRESENT`, `RETARD`, `ABSENT`, `EXCUSE`. Le champ `present`
reste la valeur lue par toutes les progressions : vrai pour présent ou en retard.

Une formation est considérée terminée lorsque la session a au moins une séance et
que toutes ses séances sont passées.

## 3. Attestations : validation avant délivrance

Une attestation n'est jamais délivrée automatiquement.

1. La page Attestations liste les formations terminées dont l'apprenant n'a pas
   encore d'attestation (inscriptions validées ou formées).
2. Un membre habilité (droit de validation sur les dossiers apprenants) clique sur
   « Valider et générer ». L'attestation est créée avec sa référence `DAG-ATT-…`, la
   date d'émission et le membre qui l'a validée.
3. À partir de là seulement, l'apprenant la voit comme « Validée » dans son dossier
   et peut la télécharger.
4. Une attestation existe au plus une fois par apprenant et par formation (409 sinon).

Le PDF est généré à la demande (`resources/views/pdf/attestation.blade.php`), au
format paysage : logo, titre, nom, formation, date, sceau, cachet et signature.
L'administration le télécharge depuis la page Attestations et la fiche apprenant ;
l'apprenant ne peut télécharger que les siennes.

## 4. Droits

Le domaine « Dossiers apprenants » de la matrice gouverne la validation
(valider), le téléchargement et la lecture des attestations et des dossiers.

Le domaine « Formations & sessions » gouverne la présence : lecture pour voir les
feuilles, modification pour pointer, téléchargement pour l'export.

## 5. Pointage de la présence par l'équipe

### Page de la session

- Chaque séance affiche son état : **À venir**, **À pointer**, **x/y pointés**
  (feuille incomplète) ou **x/y présents** (feuille complète). Le nombre de séances
  passées encore à pointer est rappelé en tête du tableau.
- Bouton **Pointer** sur les séances à pointer, **Voir la présence** sur les autres.
- **Assiduité des apprenants** : présences, retards, absences (dont excusées) et
  taux d'assiduité, calculé sur les séances déjà passées et pointées.
- **Exporter la feuille de présence** : fichier CSV (séparateur `;`, lisible par
  Excel avec les accents), une colonne par séance, totaux et taux par apprenant.

### Feuille de présence d'une séance

- Seuls les apprenants dont la place est confirmée (`VALIDEE`, `FORMEE`) y figurent,
  par ordre alphabétique.
- Quatre statuts par apprenant : **Présent**, **En retard**, **Absent**, **Excusé**,
  avec une remarque facultative (motif d'absence, durée du retard…).
- **Marquer les non pointés présents** en un clic, puis corriger les exceptions.
- Compteurs en direct, recherche dès que la liste dépasse huit apprenants, passage à
  la séance précédente ou suivante, barre d'enregistrement qui indique les
  modifications non enregistrées.
- La feuille s'ouvre le jour de la séance : une séance future ne peut pas être
  pointée.
- Chaque ligne garde qui l'a pointée et quand ; chaque enregistrement est inscrit au
  journal d'activité (« a pointé la présence », avec le détail par statut).
- Pointer à nouveau corrige les lignes existantes, sans doublon.

### Côté apprenant

La progression affiche « présent (en retard) » et « absence excusée » ; un retard
compte comme une présence dans le pourcentage de progression.

## 6. Tests

`FormationTest`, `InscriptionTest`, `AttestationTest` (validation, traçabilité,
téléchargement admin et apprenant), `PresenceTest` (feuille limitée aux places
confirmées, statuts, séance future refusée, apprenant hors session refusé, droits,
état des séances, assiduité, export) côté administration ; `ApprenantFormationTest`,
`ApprenantInscriptionTest`, `ApprenantProgressionTest`, `ApprenantDossierTest` côté
apprenant.
