# Phase 4 — Espace client (demandes, suivi, documents)

Statut : **terminée**. Correspond à la ligne « Espace client : demandes,
suivi, documents » du plan (section 6 de `docs/00-analyse-et-plan.md`).

> Première version construite sans maquette (l'utilisateur avait demandé de
> concevoir l'interface en suivant la charte graphique DAG en attendant).
> L'utilisateur a ensuite fourni les captures d'écran réelles de tout
> l'espace client (tableau de bord, services, mes demandes, abonnements,
> factures, contrats, documents, paramètres) avec la consigne de reproduire
> exactement ces maquettes. Cette section décrit l'état final, conforme aux
> maquettes.

> **Mise à jour** : un `Client` n'a pas un mais **trois visages** selon
> `clients.type` (PARTICULIER, ENTREPRISE, ORGANISATION) — tous les trois
> partagent le même compte (`UserType::Client`) et le même préfixe de routes
> `espace-client`, mais une Entreprise validée comme partenaire de
> recrutement voit un espace radicalement différent (offres, candidatures,
> conventions). Voir section 8. Organisation reste à construire dès que sa
> maquette arrive — jusque-là elle utilise le même espace générique que
> Particulier.

## 1. Ce qui existait déjà (phase 2)

Le routing (`routes/espaces.php`), le middleware `EnsureUserType`, et un
dashboard minimaliste (Tailwind/shadcn brut) existaient depuis la phase 2 —
voir `docs/02-phase-2-authentification-roles.md` §8.

## 2. Habillage visuel — barre latérale, pas topbar

Le dashboard générique Tailwind/shadcn (sidebar Teams du starter kit) a été
abandonné au profit du système `.dag` (charte DAG). `resources/js/app.tsx`
exclut `espaces/*` du layout `AppLayout` — comme les pages `public/*`, elles
fournissent leur propre habillage via
`resources/js/layouts/espace-client-layout.tsx` :

- **Barre latérale fixe** (fond navy) : logo, navigation à icônes (Tableau de
  bord, Services, Mes demandes, Abonnements, Factures, Contrats, Documents,
  Paramètres), bloc « Besoin d'aide ? » en bas. Repliable en tiroir sur
  mobile (`<960px`, bouton hamburger dans l'en-tête).
- **En-tête** séparé : barre de recherche (décorative, aucune recherche
  réelle branchée), cloche de notification (idem, pas de système de
  notifications construit), avatar avec initiales, nom + type de compte,
  déconnexion.
- Ce changement de layout concerne aussi les dashboards apprenant/admin
  (toujours minimalistes) : ils perdent la sidebar Teams, sans changement de
  contenu.

## 3. Écrans et routes (préfixe `espace-client`, middleware `CLIENT`)

| Route                                | Contrôleur                                      | Page Inertia                       |
| ------------------------------------ | ----------------------------------------------- | ---------------------------------- |
| `GET /espace-client`                 | `ClientDashboardController`                     | `espaces/client-dashboard`         |
| `GET /espace-client/services`        | `ClientServiceController`                       | `espaces/client/services-index`    |
| `GET /espace-client/demandes`        | `ClientDemandeController@index`                 | `espaces/client/demandes-index`    |
| `GET .../demandes/creer`             | `ClientDemandeController@create`                | `espaces/client/demandes-create`   |
| `POST .../demandes`                  | `ClientDemandeController@store`                 | —                                  |
| `GET .../demandes/{demande}`         | `ClientDemandeController@show`                  | `espaces/client/demandes-show`     |
| `GET /espace-client/abonnements`     | `ClientAbonnementController`                    | `espaces/client/abonnements-index` |
| `GET /espace-client/factures`        | `ClientFactureController`                       | `espaces/client/factures-index`    |
| `GET /espace-client/contrats`        | `ClientContratController`                       | `espaces/client/contrats-index`    |
| `GET /espace-client/documents`       | `ClientDocumentController`                      | `espaces/client/documents`         |
| `GET /espace-client/parametres`      | `ClientParametreController@edit`                | `espaces/client/parametres`        |
| `PATCH .../parametres`               | `ClientParametreController@update`              | —                                  |
| `PATCH .../parametres/notifications` | `ClientParametreController@updateNotifications` | —                                  |

- **Dashboard** : bannière héro (photo), 4 cartes (demandes en cours,
  factures en attente + montant restant, prochaine échéance, contrats à
  signer), bloc « Actions rapides », flux « Activité récente », carte
  formule active (progression du cycle, services inclus).
- **Services** : catalogue public réutilisé dans l'espace client (mêmes
  filtres recherche/catégorie/tri), chaque service renvoie vers « Nouvelle
  demande » avec le service pré-sélectionné (`?service=<id>`).
- **Demandes** : tableau avec onglets de statut (Toutes / En cours / Devis
  envoyé / Transmis / Terminé). Une demande transmise à Nova-Tech
  (`est_partenaire_nova_tech`) affiche le libellé de son
  `avancement_partenaire` plutôt que son statut normal — voir
  `Demande::libelleStatutPartenaire()`. Création : **un service ponctuel OU
  une formule d'abonnement** (jamais les deux — validés côté serveur),
  démarre au statut `EN_ETUDE`.
- **Suivi** (détail d'une demande) : piste de progression
  (`.process-track-card`, `Demande::SUIVI_STEPS`). `REFUSEE` est un état
  terminal affiché à part, atteignable depuis n'importe quelle étape.
- **Abonnements** : formule active (barre de progression du cycle de
  facturation via `Abonnement::progressionCycle()`, services inclus) +
  historique de facturation. « Renégocier »/« Changer de formule » sont
  visibles mais désactivés (pas de workflow de changement de formule
  construit).
- **Factures** : 3 indicateurs (réglé cette année, en attente, moyen de
  paiement le plus utilisé) + tableau. Paiement en ligne et export PDF
  affichés mais désactivés (phase 5).
- **Contrats** : liste avec action « Importer ma signature » pour un contrat
  `ENVOYE` — affichée mais désactivée (signature par photo importée, phase
  6).
- **Documents** : vue combinée contrats + factures (tableaux), pour qui
  veut tout voir au même endroit sans naviguer entre Factures et Contrats.
- **Paramètres** : 3 onglets — informations personnelles (nom, téléphone,
  e-mail ; met à jour `Client` et `User` ensemble), sécurité (change le mot
  de passe via la route Fortify existante `user-password.update`, pas de
  logique dupliquée), notifications (3 préférences, voir §5). Zone
  sensible : suppression de compte, réutilise `ProfileController::destroy`
  du starter kit derrière une modale `.dag` custom (le composant
  `DeleteUser` du starter kit est Tailwind/shadcn — mélanger les deux styles
  dans une même modale aurait été incohérent).

### Actions volontairement désactivées

Plusieurs boutons existent visuellement (conformes à la maquette) mais sont
`disabled` avec `title="Disponible prochainement"` car le workflow réel
n'est pas construit : payer une facture, importer une signature, renégocier/
changer de formule. Chacun dépend d'une phase future (5 : paiement : 6 :
signature). Les rendre cliquables sans backend aurait été trompeur.

## 4. Autorisation

`App\Policies\DemandePolicy::view()` vérifie que la demande consultée
appartient bien au client authentifié (`$demande->client_id ===
$user->client->id`), via `Gate::authorize('view', $demande)` dans
`ClientDemandeController@show`. Sans ça, un client pourrait consulter la
demande d'un autre client en devinant son UUID dans l'URL. Les autres
contrôleurs (`ClientFactureController`, `ClientContratController`,
`ClientAbonnementController`, `ClientDocumentController`) ne prennent jamais
d'ID en paramètre de route — ils listent uniquement
`$request->user()->client->...()`, donc rien à autoriser séparément.

## 5. Modèles, migrations et factories

`Client::demandes()/abonnements()/contrats()/factures()` existaient déjà
(phase 1). Cette phase ajoute :

- `Demande::libelleObjet()`, `Contrat::libelleObjet()`,
  `Facture::libelleObjet()` — libellé d'affichage (nom du service ou de la
  formule), centralisés sur chaque modèle pour éviter la duplication entre
  les contrôleurs. Vérifient les colonnes `*_id` plutôt que les relations
  chargées — voir la note Larastan ci-dessous.
- `Demande::SUIVI_STEPS`, `Demande::libelleStatutPartenaire()`.
- `Abonnement::progressionCycle()` — pourcentage du cycle de facturation
  écoulé, réutilisé par le dashboard et la page Abonnements.
- `Client::typeLabel()` — libellé FR du type de compte (Particulier /
  Entreprise / Organisation).
- Migration `add_notification_preferences_to_users_table` : 3 colonnes
  booléennes (`notif_devis`, `notif_facture`, `notif_offres`) sur `users`,
  pour la page Paramètres (aucune table de préférences n'existait).
- Factories `DemandeFactory`, `FactureFactory`, `ContratFactory` (absentes
  jusqu'ici) + trait `HasFactory` ajouté aux 3 modèles.

### Piège Larastan : le cast `date`

Une colonne castée `'date'` (par opposition à `'datetime'`) est vue par
Larastan comme `string`, pas `Carbon`, dans ce projet — alors que les deux
devraient être équivalents côté Eloquent. Confirmé en isolant le problème :
aucune colonne `'date'` existante (`Apprenant::date_naissance`,
`FormationSession::date_debut`...) n'avait jamais été chaînée avec une
méthode Carbon avant cette phase, donc le problème existait déjà, silencieux.
Contournement appliqué partout où c'est nécessaire : normaliser
explicitement avec `Carbon::parse($valeur)` avant d'appeler une méthode
Carbon, plutôt que `?->toDateString()` direct — correct au runtime dans les
deux cas (`Carbon::parse` accepte aussi bien une instance `Carbon` qu'une
chaîne), et ça satisfait le type que Larastan infère réellement.

## 6. Tests

`tests/Feature/Espaces/ClientDashboardTest.php`,
`tests/Feature/Espaces/Client/DemandesTest.php` et
`tests/Feature/Espaces/Client/DocumentsTest.php` : isolation entre clients
(un client ne voit jamais les demandes/documents d'un autre), validation de
la création de demande (service xor formule, description requise), calcul
des statistiques du dashboard, accès refusé pour un invité.

## 7. Espace Entreprise partenaire (recrutement)

Deuxième lot de maquettes reçu après la première passe de cette phase :
l'espace d'une Entreprise validée comme partenaire de recrutement
(ex. « Kiro Digital Solutions »). Diffère du dashboard générique par un
conflit d'architecture résolu avec l'utilisateur avant implémentation : le
modèle `EntreprisePartenaire` avait été conçu en phase 2 comme géré
uniquement par l'équipe DAG, sans compte ni connexion — la maquette montrait
pourtant une entreprise se connectant elle-même. Décision prise :
**validation DAG d'abord**. Une entreprise s'inscrit normalement (espace
générique, comme Particulier/Organisation) ; les fonctionnalités de
recrutement ne s'activent que si DAG la relie ensuite à un
`EntreprisePartenaire` (`entreprises_partenaires.client_id`, nouvelle
colonne, nullable + unique) dont le `statut` est `ACTIF` —
`Client::estPartenaireValide()`. Aucune UI admin pour faire cette validation
n'existe encore (l'espace admin lui-même n'est pas construit, phase 10) :
le lien se fait pour l'instant en base, comme `EntreprisePartenaire` et
`OffreEmploi` l'étaient déjà avant cette phase.

### Garde d'accès

`App\Http\Middleware\EnsurePartenaireValide`, appliqué à un sous-groupe des
routes `espace-client` (offres, candidatures, profils-proposes) en plus du
`EnsureUserType::class.':CLIENT'` habituel. Retourne 403 si
`estPartenaireValide()` est faux. Le menu latéral ne montre le groupe
« Recrutement » que dans ce cas (`EspaceClientLayout`, nav conditionnée par
`client.type === 'ENTREPRISE'` et `client.estPartenaireValide`) — évite des
liens morts plutôt que de les laisser visibles et renvoyer un 403.

### Double facturation : client_id et entreprise_id

`demandes`/`abonnements`/`contrats`/`factures` portent chacun un `client_id`
**et** un `entreprise_id`, mutuellement exclusifs sur une même ligne (motif
déjà présent avant cette phase). Un partenaire validé peut avoir des
enregistrements sous l'une ou l'autre colonne — facturé comme simple client
avant validation, et/ou via la relation partenaire après.
`Client::enregistrementsClientOuEntreprise(Model::class)` fusionne les deux
sources en une requête ; tous les contrôleurs d'espace client déjà construits
(Factures, Abonnements, Contrats, Documents, le dashboard) l'utilisent
maintenant au lieu de la relation directe `client->factures()` etc., pour
que les données de Kiro (facturées via `entreprise_id`, comme avant cette
phase) restent visibles.

### Écrans et routes ajoutés

| Route                            | Contrôleur                             | Page Inertia                            |
| -------------------------------- | -------------------------------------- | --------------------------------------- |
| `GET /espace-client` (si validé) | `ClientDashboardController` (branche)  | `espaces/client/entreprise-dashboard`   |
| `GET .../offres`                 | `ClientOffreController@index`          | `espaces/client/offres-index`           |
| `GET .../offres/creer`           | `ClientOffreController@create`         | `espaces/client/offres-create`          |
| `POST .../offres`                | `ClientOffreController@store`          | —                                       |
| `GET .../offres/{offre}`         | `ClientOffreController@show`           | `espaces/client/offres-show`            |
| `GET .../candidatures`           | `ClientCandidatureController@index`    | `espaces/client/candidatures-index`     |
| `PATCH .../candidatures/{c}`     | `ClientCandidatureController@update`   | —                                       |
| `GET .../profils-proposes`       | `ClientProfilProposeController@index`  | `espaces/client/profils-proposes-index` |
| `PATCH .../profils-proposes/{p}` | `ClientProfilProposeController@update` | —                                       |
| `GET /espace-client/paiements`   | `ClientPaiementController` (tout type) | `espaces/client/paiements-index`        |

- **Dashboard entreprise** : bannière, 4 cartes (offres actives, candidatures
  reçues, placements réalisés, taux de conversion), actions rapides, « Mes
  offres » (aperçu des 5 dernières) + donut de répartition des candidatures
  (`components/espaces/repartition-donut.tsx`, SVG fait main — premier
  graphique du projet, pas de librairie ajoutée).
- **Offres** : l'entreprise publie elle-même (`statut` forcé à `PUBLIEE`),
  liste ses offres avec aperçu des candidatures (3 dernières), page détail
  avec la liste complète et les actions Sélectionner/Refuser.
- **Candidatures** : vue transverse de toutes les candidatures reçues (pas
  liées à une offre précise dans l'URL), même actions.
- **Profils proposés par DAG** : nouvelle table `profils_proposes`
  (`entreprise_id`, `apprenant_id`, `membre_equipe_id` nullable,
  `descriptif`, `statut` PROPOSE/RETENU/REFUSE) — un profil que DAG suggère
  directement, hors de toute offre publiée. Création réservée à l'équipe
  DAG (pas construite, même raison que la validation partenaire) ; l'espace
  client peut seulement consulter et décider.
- **Mes paiements** : liste en lecture seule des `Paiement` liés aux
  factures du client (toujours via `enregistrementsClientOuEntreprise`) —
  disponible pour tous les types de client, mais ajoutée au menu seulement
  pour Entreprise pour l'instant (pas dans la maquette Particulier).

### Autorisation

`App\Policies\OffreEmploiPolicy`, `CandidaturePolicy`, `ProfilProposePolicy`
— même style qu'`DemandePolicy` : comparent
`$user->client?->entreprisePartenaire?->id` au `entreprise_id` de la
ressource (en passant par l'offre pour une candidature).

### Tests

`tests/Feature/Espaces/Client/EntrepriseTest.php` : 403 pour une entreprise
non validée ou dont le partenaire est `EN_ATTENTE`/`INACTIF`, bascule
dashboard générique ↔ entreprise selon la validation, publication d'offre,
décision sur candidature, isolation entre deux partenaires (un ne peut pas
agir sur les candidatures/offres de l'autre), factures/contrats facturés via
`entreprise_id` toujours visibles dans l'espace. Le helper
`clientPartenaireValide()` vit dans `tests/Pest.php`, comme la convention du
projet le prévoit déjà pour les fonctions globales réutilisables (voir le
commentaire « Functions » du fichier) — pas inventé pour cette phase.
Rappel pour la prochaine fois : `tests/` n'est pas dans les `paths` de
`phpstan.neon` (seuls `app/`, `bootstrap/app.php`, `config/`, `database/`,
`routes/` le sont), donc `vendor/bin/phpstan analyse tests/...` en ciblant
explicitement ce dossier sort du périmètre configuré du projet et déclenche
de faux positifs (`$this` dans les closures `test()` mal résolu) sans
rapport avec le code — seule la suite Pest valide ces fichiers.

## 8. Hors périmètre (volontairement)

- Paiement d'une demande ou d'une facture (devis → paiement en ligne) :
  phase 5.
- Signature de contrat par photo importée : phase 6.
- Recherche réelle dans la barre de recherche de l'en-tête, et notifications
  réelles (la cloche est décorative) : aucune maquette ni demande explicite
  pour ces deux fonctionnalités au-delà de leur présence visuelle.
- Un client ne peut pas annuler ou modifier une demande après l'avoir
  soumise — à faire si le besoin est confirmé.
- Espace Organisation (3ᵉ visage du `Client`) : en attente de ses propres
  maquettes.
- UI admin pour valider une entreprise comme partenaire ou créer un
  `ProfilPropose` : phase 10 (espace administrateur). Le lien
  `entreprises_partenaires.client_id` et les propositions se font en base
  en attendant.
