# Phase 7 — Module Formation

Statut : **partielle**. Correspond à la ligne « Module Formation (inscription,
présence, attestation) » du plan. Les formations, sessions, inscriptions et
attestations sont opérationnelles de bout en bout ; le pointage de la présence par
l'équipe n'a pas encore d'écran.

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

Statuts d'inscription : `BROUILLON`, `LISTE_ATTENTE`, `EN_ATTENTE_PAIEMENT`,
`VALIDEE`, `FORMEE`, `ABANDONNEE`.

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

## 5. Reste à faire

- Écran de pointage de la présence par séance (les présences sont aujourd'hui lues,
  affichées et prises en compte dans la progression, mais saisies uniquement par les
  données de démonstration).

## 6. Tests

`FormationTest`, `InscriptionTest`, `AttestationTest` (validation, traçabilité,
téléchargement admin et apprenant) côté administration ; `ApprenantFormationTest`,
`ApprenantInscriptionTest`, `ApprenantProgressionTest`, `ApprenantDossierTest` côté
apprenant.
