# Phase 5 — Paiement et facturation

Statut : **partielle** : le parcours complet fonctionne avec une passerelle de simulation. Correspond à la ligne
« Paiement et facturation » du plan (section 6 de `docs/00-analyse-et-plan.md`).
Reste à faire : le branchement d'un prestataire réel (Orange Money, MTN MoMo, carte), qui
attend l'ouverture d'un compte chez lui (voir `docs/12-services-externes-a-brancher.md`).

## 1. Parcours de bout en bout

| Étape                   | Acteur                                 | Résultat                                                                                                                                                                                             |
| ----------------------- | -------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1. Demande d'un service | Client                                 | Une demande est créée au statut « À l'étude ».                                                                                                                                                       |
| 2. Envoi du devis       | Administrateur                         | Le montant et un message optionnel sont enregistrés, la demande passe à « Devis envoyé » et la facture correspondante est émise au statut « En attente ».                                            |
| 3. Paiement             | Client                                 | Choix du moyen de paiement, saisie du numéro mobile, confirmation.                                                                                                                                   |
| 4. Confirmation         | Prestataire (ou administrateur)        | Le paiement passe à « Confirmé », la facture à « Payé », la demande à « Payé ».                                                                                                                      |
| 5. Facture              | Client                                 | Téléchargement du PDF de la facture acquittée.                                                                                                                                                       |
| 6. Contrat              | Système, puis administrateur et client | Le brouillon du contrat est généré automatiquement dès la facture soldée ; l'administration l'envoie, le client le signe, et la prestation peut alors démarrer (voir `docs/07-phase-6-contrats.md`). |

Le même parcours de paiement existe pour l'apprenant sur ses factures de formation.
Le PDF de la facture n'est proposé qu'aux espaces client et apprenant, jamais à
l'administration.

## 2. Règles métier

- **Montant** : le paiement en ligne règle toujours le solde restant de la facture.
- **Une tentative à la fois** : tant qu'un paiement est en attente de confirmation
  (délai configurable, 15 minutes par défaut), un nouveau paiement est refusé.
- **Facture déjà réglée** : aucun nouveau paiement n'est accepté.
- **Propriété** : seul le titulaire de la facture (client, entreprise liée ou apprenant)
  peut la payer ; toute autre tentative renvoie une erreur 404.
- **Numéro mobile** : 9 chiffres commençant par 6, avec ou sans l'indicatif +237.
- **Devis renvoyé** : tant qu'aucun paiement n'est enregistré, renvoyer un devis ajuste
  le montant de la facture existante ; après un paiement, le devis est verrouillé.
- **Paiement manuel** (espèces, virement, etc.) : enregistré par l'administration,
  il suit exactement la même règle d'application sur la facture que les paiements en ligne.

## 3. Architecture

Tout le code se trouve dans `app/Support/Paiements/`.

| Élément                     | Rôle                                                                                                                                        |
| --------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------- |
| `PaiementService`           | Unique point d'application de l'argent sur une facture : initiation, confirmation, échec, paiement manuel. La confirmation est idempotente. |
| `PasserellePaiement`        | Interface d'un prestataire : `initier()` démarre le paiement et renvoie la référence de transaction.                                        |
| `SimulationPasserelle`      | Prestataire fictif pour le développement et la recette. Refusé en production.                                                               |
| `PaiementEnLigneController` | Initiation du paiement et écran de simulation.                                                                                              |
| `PaiementWebhookController` | Réception des notifications signées du prestataire.                                                                                         |
| `config/paiements.php`      | Passerelle active, moyens proposés, secret du webhook, délai d'attente.                                                                     |

### Statuts

- Paiement : `EN_ATTENTE`, `CONFIRME`, `ECHEC`.
- Facture : `EN_ATTENTE`, `PARTIELLEMENT_PAYEE`, `PAYEE`.

## 4. Webhook du prestataire

- URL : `POST /webhooks/paiements` (hors protection CSRF).
- Corps JSON : `{"reference": "...", "statut": "success" | "failed"}`.
- Signature : en-tête `X-Signature`, HMAC SHA-256 du corps brut avec le secret
  `PAIEMENT_WEBHOOK_SECRET`. Sans secret configuré, toutes les requêtes sont refusées.
- Réponses : 403 (signature invalide ou secret absent), 404 (référence inconnue),
  200 (traité). Une notification rejouée est sans effet.

## 5. Administration

- La fiche d'une facture affiche le statut de chaque paiement.
- Un administrateur disposant du droit de modification sur « Factures et paiements »
  peut confirmer ou marquer en échec un paiement resté en attente, par exemple lorsque
  la notification du prestataire n'est jamais arrivée.
- L'enregistrement d'un paiement manuel reste réservé au droit de création.

## 6. Brancher un prestataire réel

1. Ouvrir un compte chez le prestataire retenu et obtenir ses clés API.
2. Créer une classe implémentant `PasserellePaiement` (initiation chez le prestataire,
   renvoi de sa référence de transaction).
3. La déclarer dans `config/paiements.php` (clé `passerelles`).
4. Renseigner dans `.env` : `PAIEMENT_PASSERELLE` et `PAIEMENT_WEBHOOK_SECRET`.
5. Configurer chez le prestataire l'URL du webhook et, si son format diffère, adapter
   sa notification au corps JSON décrit ci-dessus.

## 7. Tests

`tests/Feature/Espaces/PaiementEnLigneTest.php` couvre : le parcours complet de la
demande à la facture téléchargée, le paiement par un apprenant, l'échec puis la
nouvelle tentative, le blocage d'un second paiement en attente, la validation du
numéro mobile, le refus d'une facture déjà payée ou d'un autre titulaire, le webhook
(signature valide, rejouée, invalide, secret absent) et la confirmation manuelle par
l'administration.
