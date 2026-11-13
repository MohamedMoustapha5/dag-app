# Phase 10 — Notifications

Statut : **partielle**. Correspond à la ligne « Notifications (e-mail d'abord,
architecture prête pour WhatsApp/SMS) » du plan (section 6 de
`docs/00-analyse-et-plan.md`). L'e-mail est actif ; le SMS et WhatsApp sont déjà
branchés dans l'architecture et s'activent sans modifier le code métier. Restent à obtenir
et à brancher : le serveur d'e-mail professionnel, les adresses réelles de l'équipe, un
fournisseur SMS et un compte WhatsApp Business (voir `docs/12-services-externes-a-brancher.md`).

## 1. Ce qui est notifié

### Clients et entreprises

| Événement                                 | Notification                                                             | Préférence du compte      |
| ----------------------------------------- | ------------------------------------------------------------------------ | ------------------------- |
| Devis envoyé par DAG                      | « Votre devis est prêt » : montant, message de l'équipe, lien pour payer | `notif_devis`             |
| Paiement confirmé (en ligne ou manuel)    | « Paiement reçu » : montant, facture soldée ou solde restant             | `notif_facture`           |
| Contrat envoyé par DAG                    | « Votre contrat est prêt à signer »                                      | `notif_devis`             |
| Contrat non signé depuis plusieurs jours  | Relance automatique                                                      | `notif_devis`             |
| Contrat signé                             | Confirmation au client                                                   | `notif_devis`             |
| Candidat proposé par DAG à une entreprise | « DAG vous propose un candidat »                                         | aucune (toujours envoyée) |

### Apprenants

| Événement                      | Notification                         | Préférence du compte |
| ------------------------------ | ------------------------------------ | -------------------- |
| Inscription validée ou refusée | Décision et accès au calendrier      | `notif_seances`      |
| Attestation validée            | « Votre attestation est disponible » | `notif_seances`      |
| Séance le lendemain            | Rappel avec date et lieu             | `notif_seances`      |

### Équipe DAG

Alerte par e-mail aux membres actifs dont le rôle peut agir sur le domaine concerné
(matrice de droits) :

- nouvelle demande d'un client (droit de modification sur les demandes) ;
- paiement reçu (droit de lecture sur factures et paiements) ;
- contrat signé par un client (droit de modification sur les contrats).

Un membre dont le rôle n'a pas le droit correspondant ne reçoit rien.

## 2. Architecture

| Élément                                                  | Rôle                                                                                                                                                                                                  |
| -------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `App\Notifications\NotificationDag`                      | Base de toutes les notifications : choisit les canaux, applique les préférences du compte, construit l'e-mail de marque. Une notification se limite à dire ce qui s'est passé (titre, message, lien). |
| `App\Support\Notifications\Notifier`                     | Point d'entrée unique pour envoyer. Une panne d'envoi (serveur de messagerie, adresse invalide) est signalée mais ne fait jamais échouer l'action de l'utilisateur.                                   |
| `App\Notifications\Canaux\SmsChannel`, `WhatsAppChannel` | Canaux de message court. Ils appellent le fournisseur configuré.                                                                                                                                      |
| `App\Support\Messagerie\PasserelleMessage`               | Interface d'un fournisseur SMS ou WhatsApp. `JournalPasserelle` écrit le message dans les logs en attendant un fournisseur réel.                                                                      |
| `App\Listeners\JournaliserNotification`                  | Enregistre chaque envoi (canal, destinataire, statut) dans le journal des notifications.                                                                                                              |
| `config/notifications.php`                               | Canaux actifs, fournisseurs de messagerie, file d'attente, délai de relance.                                                                                                                          |

### Canaux

- **E-mail** : actif. Les e-mails reprennent l'identité DAG (logo, bouton bleu, signature
  de l'équipe, pied de page). Expéditeur : `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME`.
- **SMS et WhatsApp** : désactivés (`NOTIFICATIONS_SMS`, `NOTIFICATIONS_WHATSAPP`). Une fois
  activés, ils envoient le même message court à partir du numéro du profil client ou
  apprenant ; sans numéro, le canal est ignoré. Aucune notification n'a à être modifiée.

### Brancher un fournisseur SMS ou WhatsApp

Ce qu'il faut obtenir (comptes, validations, modèles de message, budget) est détaillé dans
`docs/12-services-externes-a-brancher.md`. Côté code :

1. Créer une classe implémentant `PasserelleMessage` (appel à l'API du fournisseur).
2. La déclarer dans `config/notifications.php`, clé `messagerie.fournisseurs`, puis la choisir
   avec `NOTIFICATIONS_SMS_FOURNISSEUR` ou `NOTIFICATIONS_WHATSAPP_FOURNISSEUR`.
3. Passer `NOTIFICATIONS_SMS=true` (ou `NOTIFICATIONS_WHATSAPP=true`) dans le `.env`.

### File d'attente

Par défaut (`NOTIFICATIONS_QUEUE=sync`), les e-mails partent pendant la requête, sans worker.
En production, définir `NOTIFICATIONS_QUEUE=database` (ou `redis`) et lancer un worker
(`php artisan queue:work`) pour ne jamais ralentir l'utilisateur.

## 3. Tâches planifiées

Deux commandes tournent chaque jour (le planificateur doit être lancé :
`php artisan schedule:work` en local, une entrée cron `schedule:run` en production).

| Commande                          | Heure | Rôle                                                                                                                                                                               |
| --------------------------------- | ----- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `notifications:rappels-seances`   | 08:00 | Rappelle leur séance du lendemain aux apprenants dont l'inscription est validée. Ne rappelle jamais deux fois la même séance.                                                      |
| `notifications:relancer-contrats` | 09:00 | Relance les clients dont le contrat envoyé n'est pas signé depuis 3 jours (délai configurable), puis tous les 3 jours. La date de relance n'altère pas la date d'envoi du contrat. |

## 4. Cloche de notifications (espaces client, apprenant et administrateur)

Chaque notification envoyée par e-mail est aussi déposée dans la cloche de l'en-tête :

- [x] Pastille avec le nombre de notifications non lues (« 99+ » au-delà)
- [x] Panneau déroulant : les 15 dernières, les non lues surlignées, la date relative
      (« Il y a 5 min »), message vide si aucune
- [x] Un clic ouvre la page concernée (demande, facture, contrat, inscription…) et marque la
      notification comme lue ; « Tout marquer comme lu » dans l'en-tête du panneau
- [x] Mise à jour automatique toutes les 60 secondes, sans recharger la page
- [x] Fermeture au clic extérieur ou avec Échap, panneau adapté au mobile
- [x] Toujours reçue dans la cloche, même si le client a désactivé l'e-mail : les préférences
      ne coupent que les messages envoyés hors de l'application
- [x] Équipe : la cloche de l'espace administrateur reçoit les alertes (nouvelle demande,
      paiement, contrat signé, renouvellement) selon les droits du rôle
- [x] Cloisonnement : on ne peut lire ni ouvrir la notification d'un autre compte ; un lien
      qui ne serait pas interne à l'application n'est jamais suivi

Techniquement : canal `database` de Laravel (table `notifications`, identifiant de
destinataire en texte car les comptes utilisent des identifiants numériques et l'équipe des
UUID), donnée partagée `cloche` dans toutes les pages (nom distinct de la donnée `notifications` des pages Statistiques et Paramètres), composant
`resources/js/components/espaces/cloche-notifications.tsx`, actions
`NotificationCentreController` (routes `notifications.*` et `espace-admin.notifications.*`).
Le journal des envois de Statistiques ne compte pas la cloche, seulement les e-mails, SMS et
WhatsApp.

## 5. Suivi côté administration

Statistiques, onglet **Notifications** (réservé à la Directrice) : toutes les notifications
envoyées ou en échec, avec date, destinataire, notification, canal et statut, une recherche,
un filtre par statut et l'état des canaux (e-mail actif, SMS et WhatsApp à venir).

## 6. Tests

`tests/Feature/Notifications/NotificationsTest.php` : préférences respectées, contenu de
l'e-mail, journal des envois, canal SMS prêt mais inactif, panne d'envoi sans impact,
chaque événement déclencheur (devis, paiement, contrat, inscription, attestation,
candidature, nouvelle demande), rappels de séance et relances sans doublon, onglet
Notifications réservé à la Directrice.
