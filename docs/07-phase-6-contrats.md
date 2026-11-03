# Phase 6 — Contrats et signature

Statut : **partielle**. Correspond à la ligne « Contrats et signature par photo
importée » du plan (section 6 de `docs/00-analyse-et-plan.md`). La gestion des
contrats par l'administration et le PDF du contrat signé sont en place ; la
signature par photo importée côté client reste à construire.

## 1. Réalisé

- Cycle de vie complet d'un contrat côté administration : création, envoi,
  suivi du statut, consultation.
- Modèles de contrat réutilisables et bibliothèque de clauses.
- Création d'un contrat depuis une demande payée (action « Générer le contrat »
  de la page Demandes clients).
- Consultation des contrats côté client (page Contrats et page Documents).
- PDF du contrat signé, téléchargeable par l'administration.

## 2. Données

| Table | Rôle |
| ----- | ---- |
| `modeles_contrat` | Texte type par type de contrat, au statut brouillon ou actif. |
| `clauses` | Clauses ordonnées, insérables dans un contrat. |
| `contrats` | Contrat rattaché à un client, un apprenant ou une entreprise, avec référence `DAG-CTR-…`, code de vérification, montant et statut. |

Types : `PRESTATION_SERVICE`, `ABONNEMENT`, `CONVENTION_STAGE`, `CONTRAT_PARTENAIRE`.
Statuts : `BROUILLON`, `ENVOYE`, `SIGNE`, `ACTIF`, `TERMINE`, `REFUSE`, `RESILIE`.

Le contrat porte déjà les champs de la signature électronique (nom du signataire,
date, adresse IP, photo de signature, contestation, invalidation, relance), prêts
pour la fonction de signature client.

## 3. Règles

- Une demande ne peut avoir qu'un seul contrat : une seconde création renvoie une
  erreur 409.
- Toutes les actions sont gardées par la matrice de droits « Contrats et clauses »
  (voir, créer, modifier, supprimer, télécharger).
- Le PDF n'est délivré que pour un contrat signé (statut signé, actif ou terminé,
  ou date de signature renseignée) ; sinon erreur 409.

## 4. PDF du contrat signé

Généré à la demande par `App\Support\ContratPdf` (vue `resources/views/pdf/contrat.blade.php`).

- En-tête : logo DAG, type de contrat, référence, statut.
- Encadré de contrôle : référence et code de vérification, avec la mention que le
  contrat n'est valide que si toutes les pages portent les mêmes valeurs.
- Parties et objet, puis clauses : une ligne courte commençant par « 1. », « 2. »…
  devient un titre de clause, le reste est mis en paragraphes.
- Dernière page, « Procès-verbal d'acceptation » : signataire, date et heure,
  adresse IP, contestation éventuelle, blocs de signature du cocontractant et de DAG
  (cachet et signature de la directrice).
- Pied de page répété : référence, code de vérification, numérotation « Page X sur Y ».

Les images de marque (logo, cachet) sont dans `public/images/brand/`.

## 5. Reste à faire

- Signature du contrat par le client avec une photo de signature importée, avec
  enregistrement de la date, de l'adresse IP et du code de vérification.
- Relance automatique et gestion de la contestation de signature.

## 6. Tests

`tests/Feature/Espaces/Admin/ContratTest.php` (création, statut, PDF signé, refus
d'un contrat non signé, refus sans droit) et `tests/Unit/ContratPdfTest.php`
(découpage du texte en clauses).
