# Phase 2 — Authentification par type de compte, rôles, matrice de droits

Statut : **terminée**. Correspond aux critères de validation des sections 6.2
(« un client crée un compte, se connecte... ») et 6.9 (« chaque membre de l'équipe
accède uniquement aux données et actions prévues par la matrice de droits ») du
cahier des charges.

> **Mise à jour** : le compte/espace « Entreprise partenaire » décrit ci-dessous
> a été **retiré** après cette phase, sur décision explicite de l'utilisateur :
> un partenaire échange directement avec un membre de l'équipe DAG, il n'a pas
> de rôle ni d'espace self-service dans l'application. `App\Models\EntreprisePartenaire`
> reste une entité métier normale (nom, secteur, contrats, factures, offres
> d'emploi — gérée depuis l'espace admin), mais n'a plus de compte `User`
> associé, plus de `UserType::EntreprisePartenaire`, plus de route
> `/espace-entreprise`. Les sections ci-dessous sont conservées comme trace de
> la décision initiale ; les passages concernés sont annotés _(retiré)_.

## 1. Décision d'architecture

Le starter kit Laravel installé gère l'authentification via des « équipes »
(`Team`), où un utilisateur peut appartenir à plusieurs équipes et en changer. DAG
n'a pas ce besoin : un compte est **toujours** d'un seul type fixe (Client,
Apprenant, Entreprise partenaire, ou Membre de l'équipe DAG — section 3.1). Plutôt
que de supprimer le code Teams existant (risque de casser Fortify/2FA/passkeys qui
en dépendent), les espaces DAG ont été construits **en parallèle**, hors du préfixe
`{current_team}`. Le code de redirection (`RedirectsToCurrentTeam`) a été étendu
pour prioriser le type de compte DAG, et ne retomber sur l'ancien comportement par
équipe que pour un compte sans type (cas qui ne doit plus se produire une fois
l'inscription DAG utilisée, mais garde les tests du starter kit valides).

## 2. Inscription par type de compte

`app/Actions/Fortify/CreateNewUser.php` accepte désormais un champ `compte_type`
(`particulier`, `entreprise`, `organisation`, `apprenant` — correspond au sélecteur
« Je suis » de la maquette `inscription.html`) :

- `particulier` / `entreprise` / `organisation` → crée un `User` de type `CLIENT`
  et une ligne `Client` (avec le sous-type correspondant).
- `apprenant` → crée un `User` de type `APPRENANT` et une ligne `Apprenant`.

Les comptes **Entreprise partenaire** et **Membre de l'équipe** ne sont **jamais**
créés par ce formulaire public — conformément aux sections 6.6 et 6.9 du cahier des
charges (« compte créé par DAG après validation », « seule la Promotrice peut créer
un compte »). Ces parcours seront construits dans l'espace administrateur (phase
10).

## 3. Connexion : redirection par espace, comptes bloqués

`App\Support\EspaceRedirect::pathFor($user)` calcule la destination après connexion
ou inscription, à partir de `$user->type` :

| Type          | Espace              |
| ------------- | ------------------- |
| CLIENT        | `/espace-client`    |
| APPRENANT     | `/espace-apprenant` |
| MEMBRE_EQUIPE | `/espace-admin`     |

_(La ligne `ENTREPRISE_PARTENAIRE` → `/espace-entreprise` a été retirée, voir la
mise à jour en tête de document.)_

`FortifyServiceProvider::configureAuthentication()` remplace la vérification des
identifiants par défaut pour refuser la connexion d'un **membre de l'équipe
désactivé** (`membres_equipe.statut = INACTIF`) — section 6.9 : « le membre ne
peut plus se connecter ».

Ce comportement est centralisé sur `User::peutSeConnecter()`, testable
indépendamment de la couche HTTP.

## 4. Matrice de droits (section 3.2)

Trois enums purs, sans dépendance au framework :

- `App\Enums\Donnee` — les 9 types de données de la matrice (Catalogue, Formations,
  Demandes clients, Abonnements, Factures & paiements, Contrats & clauses, Dossiers
  apprenants, Entreprises partenaires & offres, Statistiques).
- `App\Enums\Action` — les 6 actions élémentaires (voir, créer, modifier, supprimer,
  valider, télécharger).
- `App\Enums\NiveauDroit` — les 5 niveaux (Total, Gestion, Validation, Lecture,
  Aucun), chacun sachant lui-même quelles actions il autorise
  (`NiveauDroit::autorise(Action)`), exactement selon la définition du cahier des
  charges.

`App\Support\DroitsMatrix` est la transcription exacte du tableau de la section 3.2
(5 rôles × 9 données). `MembreEquipe::peut(Donnee, Action): bool` l'expose au niveau
du modèle, et renvoie toujours `false` si le membre est désactivé — donc un rôle
« Total » désactivé n'a plus aucun droit, conformément au cas d'usage de révocation
d'accès.

Cette matrice est la **brique réutilisable** pour toutes les Policies à venir
(catalogue en phase 3, factures en phase 5, contrats en phase 6, etc.) : chaque
Policy future appellera `$user->membreEquipe->peut(Donnee::X, Action::Y)` plutôt que
de redéfinir des règles d'accès.

## 5. Garde-fous de routage

`App\Http\Middleware\EnsureUserType` protège chaque groupe de routes
(`routes/espaces.php`) : un compte d'un type ne peut jamais atteindre l'espace d'un
autre type (403). Quatre contrôleurs minimalistes (`App\Http\Controllers\Espaces\*`)
exposent un tableau de bord d'accueil par espace — juste assez pour prouver que le
routage, le middleware et les données (ex. la matrice de droits pour l'espace admin)
circulent correctement de bout en bout. Le contenu détaillé de chaque espace
(catalogue, demandes, contrats, etc.) sera construit phase par phase (3 à 10), avec
l'habillage visuel des maquettes HTML.

## 6. Tests (33 nouveaux, 134 au total)

- `tests/Feature/Support/DroitsMatrixTest.php` : la matrice correspond exactement à
  la section 3.2 (jeu de données couvrant les 5 rôles), chaque niveau n'autorise que
  son propre jeu d'actions, un membre désactivé n'a aucun droit.
- `tests/Feature/Auth/RegistrationTest.php` (étendu) : un particulier devient
  Client, une entreprise devient un Client de type ENTREPRISE, un apprenant obtient
  un profil Apprenant (et aucun profil Client), un `compte_type` inconnu est rejeté.
- `tests/Feature/Auth/LoginRedirectTest.php` : chaque type de compte est redirigé
  vers le bon espace ; un membre désactivé ne peut pas se connecter.
- `tests/Feature/Espaces/EspaceAccessTest.php` : chaque espace n'est accessible qu'à
  son type de compte (403 sinon), l'espace admin expose bien le rôle et les droits
  du membre connecté, un visiteur non connecté est redirigé vers la connexion.

Aucun test existant du starter kit (Teams, 2FA, passkeys, paramètres) n'a été cassé.

## 7. Comment tester cette phase vous-même

```bash
php artisan migrate:fresh --seed
php artisan test

# Build du front nécessaire pour voir les nouvelles pages (manifeste Vite)
npm run build
php artisan serve
```

Puis, dans le navigateur :

- `/register` — créer un compte en choisissant « Je suis » (particulier, entreprise,
  organisation, apprenant), vérifier la redirection vers le bon espace.
- `/login` avec un compte de démo (mot de passe `password`) :
    - `aicha.n@exemple.com` → `/espace-client`
    - `stephanie.a@mail.com` → `/espace-apprenant`
    - `marie.d@dag.cm` (Promotrice) ou `fatou.m@dag.cm` (Juriste) → `/espace-admin`,
      avec le détail des droits du rôle affiché.
    - Essayer de visiter `/espace-admin` en étant connecté comme `aicha.n@exemple.com`
      → 403.

## 8. Limites connues / à traiter plus tard

- Les 4 tableaux de bord sont volontairement minimalistes (pas encore l'habillage
  des maquettes `espace-client.html` etc.) — le contenu réel (demandes, factures,
  contrats...) arrive module par module dans les phases suivantes.
- Le formulaire d'inscription ne reprend pas encore le split Nom/Prénom de la
  maquette `inscription.html` (un seul champ « Name », déjà présent dans le starter
  kit) — ajustement visuel mineur, à faire lors de l'habillage complet de l'espace
  client.
- La création des comptes Membre de l'équipe (par la Promotrice) n'existe pas
  encore : elle sera construite avec l'espace administrateur (phase 10).
  L'entreprise partenaire, elle, n'a plus de compte du tout (voir la mise à
  jour en tête de document) : sa fiche sera créée/éditée directement depuis
  l'espace admin, sans inscription ni connexion de son côté.
