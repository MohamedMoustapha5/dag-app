# Phase 6 — Contrats et signature

Statut : **partielle**. Correspond à la ligne « Contrats et signature par photo
importée » du plan (section 6 de `docs/00-analyse-et-plan.md`). La gestion des
contrats par l'administration, la signature par photo importée côté client et le PDF
du contrat signé sont en place ; reste la contestation de signature.

## 1. Règle : le contrat est obligatoire

Chaque service demandé et payé doit avoir un contrat signé par le client.

- **Brouillon automatique** : dès que la facture d'une demande est entièrement payée
  (paiement en ligne confirmé ou paiement manuel), le brouillon du contrat est généré
  automatiquement (une seule fois), ce qui évite tout oubli. L'administration le relit,
  puis l'envoie au client.
- **Prestation bloquée sans signature** : une demande ne peut pas passer à « En cours »,
  « Livrée » ou « Terminée » tant que son contrat n'est pas signé (message d'erreur sur le
  formulaire de statut).
- **Suivi** : la liste Demandes clients affiche, pour chaque demande payée, l'état du
  contrat (À générer, À envoyer, En attente de signature, Signé).
- **Côté client** : après paiement, la page de la demande explique que la signature du
  contrat déclenche le démarrage de la prestation.

## 2. Réalisé

- Cycle de vie complet d'un contrat côté administration : création, envoi,
  suivi du statut, consultation.
- Modèles de contrat réutilisables et bibliothèque de clauses.
- Création d'un contrat depuis une demande payée (action « Générer le contrat »
  de la page Demandes clients). Le texte est rédigé automatiquement : le modèle actif du
  type « prestation de service » avec ses champs remplacés (`{{client}}`, `{{service}}`,
  `{{montant}}`, `{{date}}`, `{{reference}}`), suivi des clauses de la bibliothèque,
  numérotées dans l'ordre.
- Envoi au client : le contrat est créé en brouillon (invisible du client) ; le bouton
  « Envoyer au client pour signature » de sa fiche le passe à « Envoyé ».
- Signature par le client : voir la section 5.
- Consultation des contrats côté client (page Contrats et page Documents).
- PDF du contrat signé, téléchargeable par l'administration.

## 3. Données

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

## 4. Règles

- Une demande ne peut avoir qu'un seul contrat : une seconde création renvoie une
  erreur 409.
- Toutes les actions sont gardées par la matrice de droits « Contrats et clauses »
  (voir, créer, modifier, supprimer, télécharger).
- Le PDF n'est délivré que pour un contrat signé (statut signé, actif ou terminé,
  ou date de signature renseignée) ; sinon erreur 409.

## 5. PDF du contrat signé

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

## 6. Signature par le client

1. Le client ouvre le contrat depuis « Contrats » (bouton « Lire et signer ») ou depuis
   sa demande ; il lit le texte intégral.
2. Il importe une image de sa signature manuscrite (PNG ou JPG, 4 Mo maximum), confirme
   son nom complet et coche l'acceptation des termes.
3. Le contrat passe à « Signé ». Sont conservés : l'image (disque privé), le nom saisi,
   la date et l'heure, l'adresse IP.
4. Le client et l'administration peuvent télécharger le PDF signé, dont le procès-verbal
   d'acceptation reprend ces éléments et la signature importée.

Règles : seul un contrat « Envoyé » peut être signé (409 sinon) ; un contrat en brouillon
est invisible du client (404) ; un client ne peut lire, signer ni télécharger que ses
propres contrats, ou ceux de l'entreprise partenaire liée à son compte.

## 7. Reste à faire

- Gestion de la contestation de signature (les champs existent en base).

La relance automatique d'un contrat envoyé et non signé est livrée avec les notifications
(`docs/11-phase-10-notifications.md`).

## 8. Tests

`tests/Feature/Espaces/Admin/ContratTest.php` (création, statut, PDF signé, refus
d'un contrat non signé, refus sans droit), `tests/Feature/Espaces/Client/ContratSignatureTest.php`
(rédaction automatique, brouillon masqué, signature, validations, accès des autres clients)
et `tests/Unit/ContratPdfTest.php` (découpage du texte en clauses).
