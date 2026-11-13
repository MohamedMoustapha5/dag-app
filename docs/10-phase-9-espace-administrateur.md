# Phase 9 — Espace administrateur

Statut : **terminée**. Correspond à la ligne « Espace administrateur (catalogue,
équipe, journal) » du plan.

## 1. Authentification séparée

Les comptes de l'équipe DAG sont isolés des comptes publics.

- Table `membres_equipe` (nom, e-mail, mot de passe, rôle, statut) distincte de `users`.
- Guard `admin` et connexion dédiée sur `/espace-admin/connexion`. Un compte public ne
  peut pas se connecter à l'administration, et réciproquement.
- Un membre désactivé est déconnecté et refusé à chaque requête (`EnsureMembreActif`).
- Une migration a déplacé les comptes équipe existants sans perte d'identifiants.

## 2. Rôles et droits évolutifs

Six rôles : Directrice (accès total), Juriste, Responsable Marketing, Secrétaire,
Création de contenu, Développeur.

- Le rôle Développeur (ajouté après les cinq rôles du cahier des charges) démarre en
  lecture et téléchargement sur tous les domaines, sans pouvoir modifier ni gérer
  l'équipe ; la Directrice peut relever chaque niveau. Son compte est aussi présenté
  dans l'équipe du site public.
- Les droits par rôle et par domaine (catalogue, formations, demandes clients,
  support, abonnements, factures et paiements, contrats et clauses, dossiers
  apprenants, entreprises et offres, statistiques) ont des valeurs par défaut issues
  du cahier des charges, modifiables depuis la page Équipe sans changer le code.
- Support (messagerie avec les clients, apprenants et visiteurs du formulaire de
  contact) a sa propre ligne : lecture pour lire les conversations, gestion pour
  répondre, clore et recevoir les alertes. Ses valeurs par défaut reprennent celles
  des demandes clients, qu'il suivait auparavant.
- Niveaux : Total, Gestion (voir, créer, modifier), Validation (voir, valider),
  Lecture (voir, télécharger), Aucun.
- Les modifications sont stockées dans `droits_roles`, mises en cache et prises en
  compte immédiatement. Les droits de la Directrice ne sont pas modifiables.
- Seule la Directrice crée les comptes, change les rôles et édite les droits ; nul ne
  peut changer son propre rôle ni se désactiver.

## 3. Tableau de bord et statistiques adaptés

Chaque indicateur n'est calculé que si le rôle a le droit de voir le domaine concerné.
Un rôle ne reçoit donc aucune donnée qu'il n'a pas le droit de voir, et l'interface
n'affiche que ce qui lui est transmis.

## 4. Pages de l'espace

| Section | Pages |
| ------- | ----- |
| Activité commerciale | Demandes clients (devis, contrat), Comptes clients et apprenants, Abonnements et formules, Factures et paiements, Contrats et clauses |
| Catalogue | Catalogue de services, Grille tarifaire |
| Formation | Formations et sessions, Attestations |
| Recrutement | Entreprises et offres, Dossiers apprenants |
| Pilotage | Statistiques |
| Administration | Équipe DAG et droits |

Les liens de la barre latérale n'apparaissent que si le rôle a au moins le droit de
voir le domaine.

## 5. Documents PDF

Les PDF d'attestation, de facture et de contrat utilisent l'identité DAG (logo,
couleurs, cachet signé de la directrice) : voir les phases 5, 6 et 7.

## 6. Statistiques, journal d'activité et documents

La page Statistiques regroupe trois onglets, affichés selon les droits du rôle.

| Onglet | Contenu | Qui le voit |
| ------ | ------- | ----------- |
| Indicateurs | Chiffres clés par domaine, demandes par statut, finances | Tout rôle ayant le droit de voir les statistiques, chaque indicateur étant filtré par ses droits |
| Journal d'activité | Qui a fait quoi, du plus récent au plus ancien | La Directrice uniquement |
| Documents | Contrats signés, attestations, CV et dossiers reçus, en un seul tableau | Les rôles ayant le droit de téléchargement sur l'un de ces domaines |

### Journal d'activité

- Chaque ligne indique l'auteur et son rôle, l'action, un détail et la date. Les événements
  sans auteur humain (paiement en ligne confirmé ou échoué, contrat signé par le client)
  apparaissent sous « Système ».
- Filtres : auteur (membre ou « Système et clients ») et recherche libre. Pagination de 20
  événements.
- Il est réservé à la Directrice car ses entrées couvrent tous les domaines : un autre rôle
  verrait des informations qu'il n'a pas le droit de voir.
- Événements enregistrés : connexion à l'administration, devis envoyé, demande mise à
  jour, contrat généré ou changé de statut, attestation validée, candidature déposée par
  DAG, partenaire validé ou désactivé, offre publiée ou clôturée, facture créée, paiement
  enregistré ou confirmé, comptes client et apprenant supprimés, dossier reçu supprimé,
  compte d'équipe créé ou modifié, droits modifiés ou réinitialisés.
- Les enregistrements passent par `ActivityLog::record()` (table `activity_logs`).

### Documents

- Liste unique, triée par date, avec filtre par famille (contrats signés, attestations,
  CV et dossiers), recherche par nom ou référence et lien de téléchargement.
- Chaque famille n'apparaît que si le rôle a le droit de téléchargement correspondant. Les
  factures n'y figurent pas : leur PDF est réservé aux espaces client et apprenant.
- Les 100 documents les plus récents sont affichés ; la recherche et le filtre permettent
  d'affiner.

## 6 bis. Suivi des abonnements et des renouvellements

L'équipe est prévenue sans avoir à chercher :

- [x] Alerte à la création d'une demande de renouvellement par un client (notification de
  l'équipe ayant le droit de modifier les demandes)
- [x] Page Abonnements : bandeau « À traiter » (renouvellements demandés, abonnements
  expirés, abonnements arrivant à échéance), onglet « À traiter », colonne Alerte avec le
  lien vers la demande de renouvellement, jours restants ou dépassés sous l'échéance
- [x] Barre de cycle découpée par mois avec l'état du paiement de chaque mois
- [x] Tableau de bord : bandeau d'alerte vers les abonnements, visible des seuls rôles ayant
  accès aux abonnements
- [x] Renouvellement par l'équipe : le bouton **Renouveler** de la colonne Alerte, ou le
  passage du statut à « Actif » pour un abonnement expiré, terminé ou en brouillon, lance un
  nouveau cycle de même durée (un mois au minimum) avec une barre de progression remise à
  zéro. Avant la fin du cycle, le nouveau cycle commence à l'ancienne échéance ; après la
  fin, il commence le jour du renouvellement. La demande de renouvellement est alors close
  (colonne `renouvele_le`), l'alerte disparaît et la facture réglée par le client est
  rattachée à l'abonnement, ce qui affiche le mois comme payé. Réactiver un abonnement
  suspendu non expiré ne touche pas ses dates.
- [ ] Prolongation automatique de l'échéance dès la réception du paiement du renouvellement
  (aujourd'hui, c'est l'équipe qui valide le renouvellement)

Côté client (`docs/05-phase-4-espace-client.md`), chaque abonnement affiche ses mois sur la
barre de progression (le mois en cours en gras, une coche verte si payé, une horloge si en
attente), un indicateur « Payé ce mois » et, après une demande, un lien de suivi à la place
du bouton Renouveler. Tests : `tests/Feature/Espaces/Client/AbonnementsTest.php`.

## 7. Tests

Un fichier par domaine dans `tests/Feature/Espaces/Admin/` (dont `EquipeTest`,
`DashboardTest`, `StatistiqueTest` qui couvre aussi le journal et les documents), `tests/Feature/Auth/LoginRedirectTest.php` pour la
connexion séparée et `tests/Feature/Support/DroitsMatrixTest.php` pour la matrice.
