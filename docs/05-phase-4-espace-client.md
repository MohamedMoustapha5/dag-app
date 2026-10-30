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

| Route                              | Contrôleur                         | Page Inertia                     |
| ----------------------------------- | ----------------------------------- | --------------------------------- |
| `GET /espace-client`               | `ClientDashboardController`        | `espaces/client-dashboard`       |
| `GET /espace-client/services`      | `ClientServiceController`          | `espaces/client/services-index`  |
| `GET /espace-client/demandes`      | `ClientDemandeController@index`    | `espaces/client/demandes-index`  |
| `GET .../demandes/creer`           | `ClientDemandeController@create`   | `espaces/client/demandes-create` |
| `POST .../demandes`                | `ClientDemandeController@store`    | —                                 |
| `GET .../demandes/{demande}`       | `ClientDemandeController@show`     | `espaces/client/demandes-show`   |
| `GET /espace-client/abonnements`   | `ClientAbonnementController`       | `espaces/client/abonnements-index` |
| `GET /espace-client/factures`      | `ClientFactureController`          | `espaces/client/factures-index`  |
| `GET /espace-client/contrats`      | `ClientContratController`          | `espaces/client/contrats-index`  |
| `GET /espace-client/documents`     | `ClientDocumentController`         | `espaces/client/documents`       |
| `GET /espace-client/parametres`    | `ClientParametreController@edit`   | `espaces/client/parametres`      |
| `PATCH .../parametres`             | `ClientParametreController@update` | —                                 |
| `PATCH .../parametres/notifications` | `ClientParametreController@updateNotifications` | — |

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

## 7. Hors périmètre (volontairement)

- Paiement d'une demande ou d'une facture (devis → paiement en ligne) :
  phase 5.
- Signature de contrat par photo importée : phase 6.
- Recherche réelle dans la barre de recherche de l'en-tête, et notifications
  réelles (la cloche est décorative) : aucune maquette ni demande explicite
  pour ces deux fonctionnalités au-delà de leur présence visuelle.
- Un client ne peut pas annuler ou modifier une demande après l'avoir
  soumise — à faire si le besoin est confirmé.
