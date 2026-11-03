# Phase 8 — Entreprises partenaires et placement

Statut : **terminée**. Correspond à la ligne « Entreprises partenaires et placement
(offres, candidatures) » du plan.

## 1. Vue d'ensemble

DAG valide des entreprises partenaires, publie leurs offres d'emploi, et place ses
apprenants (ou des candidats externes qui lui ont envoyé leur dossier) en
postulant à leur place. L'entreprise consulte les dossiers que DAG lui transmet,
puis retient ou refuse chaque candidat.

## 2. Côté administration

### Entreprises et offres

- Validation d'une entreprise (en attente, active, inactive) et publication ou
  clôture d'une offre. Rien n'est mis en ligne automatiquement.
- Chaque offre affiche sa ville, sa date limite et son nombre de candidatures. Une
  offre publiée dont la date limite est dépassée est signalée « Expirée » et peut
  être prolongée avec une nouvelle date.
- **Postuler** : le bouton est présent sur chaque offre. Il ouvre la liste de tous les
  dossiers disponibles : apprenants en parcours, apprenants formés par DAG, et dossiers
  reçus de candidats externes. Il est grisé, avec la raison, si l'offre est clôturée
  ou expirée.

### Dossiers apprenants

- Onglet « Dossiers » : tous les apprenants, filtres « Formés par DAG » et « Dossiers
  externes », recherche, accès au CV.
- Onglet « Dossiers reçus » : candidats qui ont envoyé leur dossier à DAG par le
  formulaire public de candidature spontanée (formés par DAG ou non). On y voit leurs
  coordonnées, leur message, leur portfolio et leur CV. On peut postuler pour eux ou
  supprimer le dossier.
- La fiche d'un apprenant permet de postuler directement à une offre publiée.

### Comptes

La page Comptes clients comporte un onglet « Apprenants » listant ceux qui ont créé
leur compte, avec suppression du compte (les factures et contrats sont conservés).

## 3. Côté entreprise partenaire

- Création d'offres, suivi des candidatures, consultation du dossier complet d'un
  candidat (parcours de formation, CV), puis « Sélectionner ce candidat » ou
  « Refuser ».
- Les candidatures déposées par DAG sont signalées « Dossier transmis par DAG ».

## 4. Règles

- Postuler exige le droit de création sur « Entreprises et offres » et la lecture des
  dossiers apprenants. Transformer un dossier reçu en dossier apprenant exige en plus
  le droit de création sur les dossiers apprenants.
- Une offre n'accepte des candidatures que si elle est publiée et dans ses délais.
- Un apprenant ne peut postuler qu'une fois à une même offre.
- **Consentement** : DAG ne peut pas postuler pour un apprenant qui n'a pas autorisé le
  partage de son dossier avec les entreprises. Un dossier reçu par candidature
  spontanée est considéré comme consenti.
- Une candidature déposée par DAG enregistre le membre qui l'a déposée.
- Si l'adresse e-mail d'un dossier reçu correspond à un compte apprenant existant, ce
  compte est réutilisé ; sinon un dossier externe est créé (compte sans mot de passe
  utilisable, récupérable par « mot de passe oublié »).
- Supprimer un dossier reçu supprime aussi le dossier apprenant qui en a été créé et le
  fichier du CV.

## 5. Données

`entreprises_partenaires`, `offres_emploi`, `candidatures` (avec `deposee_par_id`),
`candidatures_spontanees` (avec `apprenant_id` une fois transformée),
`profils_proposes`.

Statuts de candidature : `DEPOSEE`, `EN_ETUDE`, `RETENUE`, `REFUSEE`, `PLACEE`.

## 6. Tests

`tests/Feature/Espaces/Admin/EntrepriseTest.php`, `ApprenantTest.php`,
`CandidatExterneTest.php`, `ClientTest.php` et `tests/Feature/Espaces/Client/EntrepriseTest.php`.
