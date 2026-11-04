# Services externes à brancher avant la mise en production

Ce document liste tout ce qu'il faut obtenir ou décider pour que les envois réels
fonctionnent : e-mail professionnel, SMS, WhatsApp et paiement. Le code applicatif est
déjà prêt pour chacun (voir `docs/06-phase-5-paiement-facturation.md` et
`docs/11-phase-10-notifications.md`) ; il manque les comptes, les clés et, pour
certains services, un petit adaptateur.

> Les tarifs, la couverture des opérateurs et les délais de validation changent : tout ce
> qui est marqué « à vérifier » doit être confirmé auprès du fournisseur au moment du
> choix. Aucun fournisseur n'est retenu à ce jour.

## 1. Vue d'ensemble

| Service | État du code | Ce qui manque | Priorité |
| ------- | ------------ | ------------- | -------- |
| E-mail professionnel | Prêt, actif | Un serveur d'envoi (SMTP) et la configuration du domaine | Indispensable |
| Adresses réelles de l'équipe | Prêt | La liste des adresses à enregistrer | Indispensable |
| Paiement Orange Money, MTN MoMo, carte | Prêt, en simulation | Un compte prestataire et ses clés API, un adaptateur | Indispensable |
| SMS | Canal prêt, désactivé | Un fournisseur, un nom d'expéditeur validé, un adaptateur | Plus tard |
| WhatsApp | Canal prêt, désactivé | Un compte WhatsApp Business, des modèles de message approuvés, un adaptateur | Plus tard |

## 2. E-mail professionnel (à faire en premier)

### À fournir

- L'adresse d'expédition de DAG (par exemple `contact@dag.cm`), sur un nom de domaine que
  DAG contrôle.
- Un service d'envoi SMTP : celui de l'hébergeur du domaine, ou un service spécialisé
  d'envoi transactionnel (plusieurs existent, avec une offre gratuite pour de petits volumes :
  à comparer et à vérifier). Il fournit un hôte, un port, un identifiant et un mot de passe.
- L'accès au DNS du domaine pour ajouter les enregistrements d'authentification :
  **SPF**, **DKIM** et **DMARC**. Sans eux, une grande partie des e-mails arrive en courrier
  indésirable ou est refusée.

### Configuration (fichier `.env` du serveur)

```
MAIL_MAILER=smtp
MAIL_HOST=<hôte SMTP>
MAIL_PORT=<587 ou 465>
MAIL_USERNAME=<identifiant>
MAIL_PASSWORD=<mot de passe>
MAIL_FROM_ADDRESS="contact@dag.cm"
MAIL_FROM_NAME="DAG — Digital Agency Group"
APP_URL=https://<domaine public de la plateforme>
NOTIFICATIONS_QUEUE=database
```

- `APP_URL` doit être l'adresse publique en HTTPS : les liens des e-mails (payer, signer un
  contrat) et le logo en dépendent.
- `NOTIFICATIONS_QUEUE=database` demande un worker (`php artisan queue:work`) géré en
  permanence sur le serveur ; sans worker, laisser `sync`.
- Le planificateur doit tourner (une entrée cron `php artisan schedule:run` chaque minute)
  pour les rappels de séance et les relances de contrats.

### Vérification

Envoyer un devis à un compte de test et contrôler : réception en boîte principale, logo
affiché, lien fonctionnel, ligne « Envoyée » dans Statistiques, onglet Notifications.

### Comptes de l'équipe

Chaque membre se connecte et reçoit ses alertes avec l'adresse de son compte. Il faut donc
remplacer les adresses de démonstration (`@dag.cm` fictives) par les vraies adresses. La
page Équipe permet de créer un compte, de changer un rôle ou de désactiver un membre, mais
pas encore de modifier une adresse : une fois la liste fournie (nom, adresse, rôle), les
comptes sont mis à jour en une opération, ou la modification d'adresse est ajoutée à la page.

## 3. Paiement mobile et carte

Voir `docs/06-phase-5-paiement-facturation.md`, section « Brancher un prestataire réel ».

### À fournir

- Un compte marchand chez le prestataire choisi : une passerelle unique couvrant Orange Money,
  MTN Mobile Money et carte, ou les API directes des opérateurs (plus d'étapes de
  validation). Ouverture et validation du compte : plusieurs jours, avec les documents
  de l'entreprise (à vérifier).
- Les clés API (publique et secrète) et un environnement de test.
- L'adresse du webhook de la plateforme à déclarer chez le prestataire :
  `https://<domaine>/webhooks/paiements`, avec le secret partagé `PAIEMENT_WEBHOOK_SECRET`.

### Côté code

Une classe qui implémente `PasserellePaiement` (initier le paiement chez le prestataire,
renvoyer sa référence) et la conversion de sa notification vers le format attendu par le
webhook. La logique de facture, de contrat et de notification ne change pas.

## 4. SMS

### Ce qu'il faut

1. **Un fournisseur d'envoi de SMS** couvrant le Cameroun, avec une API HTTP. Candidats à
   évaluer : Twilio, Infobip, Africa's Talking, ou un agrégateur / une offre « SMS entreprise »
   des opérateurs locaux (à demander par devis). La couverture exacte, les tarifs au SMS et
   les délais sont **à vérifier** auprès de chacun.
2. **Un nom d'expéditeur (sender ID)** de type texte, par exemple « DAG ». Au Cameroun,
   d'après les lignes directrices de Twilio, un nom d'expéditeur alphanumérique doit être
   **enregistré à l'avance** (délai annoncé d'environ trois semaines), et sur le réseau MTN
   un numéro d'expéditeur international ne fonctionne pas : seul l'expéditeur alphanumérique
   enregistré passe. À anticiper dès le choix du fournisseur, car c'est le délai le plus long.
3. **Les clés d'accès** du fournisseur (identifiant et jeton, ou clé API) et un crédit de SMS.
4. **Des numéros exploitables** : le format international complet (`+237` suivi de 9 chiffres).
   Les numéros saisis aujourd'hui dans les profils sont enregistrés tels que tapés, avec ou
   sans indicatif ni espaces.

Sources : [lignes directrices Twilio pour le Cameroun](https://www.twilio.com/en-us/guidelines/cm/sms).

### Côté code (à faire quand le fournisseur est choisi)

- Une classe `PasserelleMessage` pour le fournisseur (un appel HTTP : numéro, texte,
  expéditeur), déclarée dans `config/notifications.php` (voir `docs/11`).
- La normalisation des numéros au format international avant envoi.
- Une case de consentement SMS par compte (aujourd'hui, les préférences portent sur le type
  d'événement — devis, factures, séances — et non sur le canal).
- Optionnel : la réception des accusés de livraison du fournisseur pour marquer un SMS en
  échec dans le journal.
- Variables : `NOTIFICATIONS_SMS=true`, `NOTIFICATIONS_SMS_FOURNISSEUR=<nom>`, plus les clés
  propres au fournisseur.

## 5. WhatsApp

### Ce qu'il faut

1. **Un compte WhatsApp Business Platform (API Cloud de Meta)**, directement ou via un
   prestataire agréé. Prérequis habituels : un compte Meta Business, la **vérification de
   l'entreprise** par Meta (la première étape et la plus longue : documents officiels), un
   numéro de téléphone dédié à l'API (qui ne doit plus être utilisé dans l'application
   WhatsApp classique) et un nom d'affichage validé.
2. **Des modèles de message approuvés par Meta.** DAG écrit à ses clients de sa propre
   initiative (devis, paiement, rappel) : WhatsApp n'autorise alors que des modèles
   préalablement soumis et approuvés, avec des variables (nom, montant, lien). Chaque
   notification devra avoir son modèle, classé « utilitaire » ou « marketing ».
3. **Un consentement explicite pour WhatsApp**, distinct de celui de l'e-mail ou du SMS, avec
   sa date. Une case cochée par défaut ou l'acceptation des conditions générales ne suffisent
   pas.
4. **Un budget** : depuis le 1er juillet 2025, Meta facture au **message** (et non plus à la
   conversation), selon la catégorie du modèle et le pays. Les tarifs sont **à vérifier**
   sur la grille officielle de Meta.
5. Les clés d'accès : un jeton d'accès permanent et l'identifiant du numéro (Phone Number ID).

Sources : [tarification officielle de la plateforme WhatsApp Business](https://developers.facebook.com/documentation/business-messaging/whatsapp/pricing).

### Côté code (à faire quand le compte est prêt)

- Une classe de fournisseur WhatsApp. **Attention** : l'interface actuelle
  `PasserelleMessage::envoyer(numéro, texte)` convient au SMS ; WhatsApp exige l'envoi d'un
  **modèle** et de ses variables. Il faudra ajouter une méthode d'envoi par modèle et faire
  renvoyer à chaque notification le nom du modèle et ses paramètres (les méthodes `toWhatsApp`
  des notifications sont prévues pour ce changement).
- Le consentement WhatsApp par compte, la normalisation des numéros, et idéalement la
  réception des statuts de livraison (webhook de Meta).
- Variables : `NOTIFICATIONS_WHATSAPP=true`, `NOTIFICATIONS_WHATSAPP_FOURNISSEUR=<nom>` et les
  clés du fournisseur.

## 6. Ordre conseillé

1. E-mail professionnel et adresses de l'équipe (aucune dépendance externe longue).
2. Compte prestataire de paiement (la validation prend du temps : le demander tôt).
3. Vérification de l'entreprise chez Meta et enregistrement du nom d'expéditeur SMS : ces
   démarches administratives peuvent être lancées en parallèle dès maintenant, même si
   l'intégration technique vient après.
4. Intégration des adaptateurs SMS et WhatsApp, puis consentements par canal.

## 7. Informations à rassembler

| Information | Pour | Qui la détient |
| ----------- | ---- | -------------- |
| Nom de domaine et accès DNS | E-mail, webhooks | DAG ou son hébergeur |
| Adresse d'expédition officielle | E-mail | DAG |
| Liste des membres : nom, adresse, rôle | Comptes équipe | DAG |
| Documents légaux de l'entreprise (registre, identifiants fiscaux) | Paiement, WhatsApp, SMS | DAG |
| Nom d'expéditeur souhaité (« DAG ») | SMS | DAG |
| Numéro dédié à WhatsApp Business | WhatsApp | DAG |
| Budget mensuel prévu (SMS, WhatsApp) | Choix des fournisseurs | DAG |
