# Phase 3 — Espace public (catalogue, formations, abonnement, contact)

Statut : **terminée**. Correspond à la section 6.1 du cahier des charges
(« un visiteur anonyme consulte le catalogue de services, les formations
disponibles, les formules d'abonnement, et peut contacter DAG ou demander un
audit gratuit sans créer de compte »).

> **Mise à jour** : après une première version générique de la page d'accueil,
> l'utilisateur a fourni des captures d'écran de sa maquette réelle (et
> l'image de fond du hero). La page d'accueil (`public/home.tsx`) a été
> entièrement reconstruite pour correspondre exactement à cette maquette —
> voir section 2bis. La page `offres-emploi.tsx` a été ajoutée à cette
> occasion car le footer de la maquette y renvoie.

## 1. Habillage visuel réel (`dag.css`)

Les pages publiques précédentes (phase 1/2) n'avaient pas d'habillage — seul
Tailwind/shadcn (le design du starter kit) était disponible, pensé pour
l'espace authentifié (dashboard). Le visiteur anonyme doit voir le design des
maquettes HTML/CSS/JS fournies par l'utilisateur (`style.css` réel, charte
graphique DAG d'octobre 2026).

- `resources/css/dag.css` porte le `style.css` réel des maquettes quasiment
  1:1, avec deux adaptations obligatoires :
    - **Scoping `.dag`** : tous les sélecteurs (`.btn-primary`, `.card`,
      `.topbar`, etc.) sont préfixés par `.dag ` pour qu'ils ne s'appliquent
      qu'à l'intérieur d'un conteneur `<div className="dag">` — jamais à
      l'espace authentifié qui utilise Tailwind/shadcn.
    - **Préfixe `--dag-`** sur les ~27 variables CSS (`--navy` → `--dag-navy`,
      `--border` → `--dag-border`, `--radius-sm/md/lg` → `--dag-radius-*`,
      etc.). Le fichier `style.css` original définissait ces variables sur
      `:root`, avec les **mêmes noms** que les tokens `@theme` de Tailwind v4
      déjà utilisés par les pages dashboard/settings/teams existantes
      (`--border`, `--radius-sm`...). Les importer sans renommage aurait
      silencieusement cassé le style de tout le reste de l'application.
    - Les `url("photos/...")` relatifs des maquettes sont réécrits en chemins
      absolus `url("/images/photos/...")` car ce fichier est servi par Vite et
      non livré à côté d'un dossier `assets/`.
- `resources/css/app.css` importe `dag.css` via `@import './dag.css';`.
- Polices : `Sora` (titres) et `Plus Jakarta Sans` (texte courant) — choix
  confirmé par l'utilisateur entre la maquette et la charte graphique
  officielle (qui utilisait Poppins/Inter). Ajoutées en auto-hébergement via
  `bunny()` dans `vite.config.ts` (`laravel-vite-plugin/fonts`).
- Photos : les 41 photos fournies par l'utilisateur ont été renommées et
  mappées (par inspection visuelle de leur contenu) vers les noms de fichiers
  exacts attendus par les maquettes (`home-hero.jpg`, `auditorium-welcome.jpg`,
  etc.), copiées dans `public/images/photos/`. Le logo réel est en
  `public/images/brand/dag-logo.jpg`.

## 2. Pages et composants partagés

Les composants partagés (répétés sur chaque page publique) vivent dans
`resources/js/components/public/` :

- `topbar.tsx` — logo, navigation, sélecteur de langue (cosmétique, FR/EN non
  branché — aucune donnée du cahier des charges ne requiert l'i18n à ce
  stade), actions (connexion/inscription ou lien vers l'espace si connecté),
  menu mobile (hamburger + panneau) piloté par un état React plutôt que par
  manipulation DOM (contrairement au `script.js` original).
- `site-footer.tsx` — navigation, coordonnées, mentions légales.
- `contact-fab.tsx` — bouton flottant (bas droite) ouvrant un panneau de
  contact rapide (WhatsApp/téléphone/email), état local `isOpen`.
- `cookie-banner.tsx` — bandeau de consentement, persistance via
  `localStorage` (`dag-cookie-consent`), s'affiche uniquement si aucun choix
  n'a encore été enregistré sur cet appareil.

`resources/js/layouts/public-layout.tsx` assemble ces éléments autour du
contenu de chaque page, dans le conteneur `.dag` qui active `dag.css`.

`resources/js/hooks/use-scroll-reveal.ts` reproduit l'effet de révélation au
défilement du `script.js` original (classes `.reveal` / `.reveal-stagger` +
`IntersectionObserver` ajoutant `is-visible`), appelé une fois par
`PublicLayout`.

**Important** : `resources/js/app.tsx` applique un `AppLayout` (sidebar du
dashboard) à toute page dont le nom ne correspond à aucun cas particulier. Un
cas `name.startsWith('public/')` → `null` a été ajouté à côté du cas
`welcome` existant, sinon chaque page publique aurait été enveloppée _en plus_
dans le shell du dashboard authentifié.

Pages (`resources/js/pages/public/`) :

| Page                   | Route                                             | Contrôleur                  |
| ---------------------- | ------------------------------------------------- | --------------------------- |
| `home.tsx`             | `GET /` (`home`)                                  | `HomeController`            |
| `catalogue.tsx`        | `GET /catalogue` (`catalogue`)                    | `CatalogueController`       |
| `formations.tsx`       | `GET /formations` (`formations`)                  | `FormationController@index` |
| `formation-detail.tsx` | `GET /formations/{formation}` (`formations.show`) | `FormationController@show`  |
| `abonnement.tsx`       | `GET /abonnement` (`abonnement`)                  | `FormuleController`         |
| `contact.tsx`          | `GET /contact` (`contact`)                        | `ContactController@index`   |

- **Catalogue** : liste les `Service::publies()` uniquement ; recherche texte
  et filtre par catégorie côté client (React `useState`/`useMemo`), pas de
  requête serveur supplémentaire — le volume de services publiés reste faible.
- **Formations** : une ligne par **session** ouverte ou complète (pas par
  formation), avec filtre par catégorie ; renvoie vers la fiche détail.
- **Fiche formation** : programme, durée, prérequis, sessions ouvertes avec
  places restantes (`FormationSession::placesRestantes()`), CTA vers le
  formulaire de contact (l'inscription effective nécessite un compte
  Apprenant — phase 7, hors périmètre ici).
- **Abonnement** : formules publiées (`Formule::where('statut', 'PUBLIEE')`),
  triées formule populaire en premier.
- **Contact** : formulaire nom/email/sujet/message → `ContactMessage`.
- **Offres de stage & emploi** (`offres-emploi.tsx`, `OffreEmploiController`,
  route `offres-emploi`) : liste les `OffreEmploi::publiees()` (statut
  `PUBLIEE` et date limite non dépassée), avec leur entreprise partenaire.
  Le bouton « Postuler » renvoie vers `/register` — une candidature est
  rattachée à un `apprenant_id` (section 8 du cahier des charges), donc un
  visiteur anonyme doit d'abord créer un compte apprenant. L'écran de
  candidature lui-même sera construit avec le module formation (phase 7).

## 2bis. Page d'accueil — fidélité à la maquette réelle

L'utilisateur a transmis des captures d'écran de sa maquette `index.html` et
l'image utilisée en fond du hero. `public/home.tsx` a été reconstruite section
par section pour correspondre exactement à ces captures :

1. **Hero** (`home-hero.jpg` remplacée par l'image exacte fournie) — badge
   « Bienvenue chez DAG », titre, 2 CTA, puis une barre de 3 statistiques :
   nombre de services publiés (dynamique, `Service::publies()->count()`),
   « 100% Contrats numériques » et « 3 Moyens de paiement » (fixes — ce ne
   sont pas des métriques stockées en base).
2. **« Comment ça marche »** — 4 étapes numérotées (01-04), contenu fixe
   correspondant au parcours décrit en section 6.1 (choisir un service,
   recevoir un devis, payer et signer en ligne, suivre la réalisation).
3. **Bannière « Comment suivre une formation DAG »** — 3 étapes avec icônes
   (`lucide-react`), renvoie vers `/formations`.
4. **Équipe** (`#equipe`) — **remplacé par des données réelles** : au lieu
   d'un contenu statique inventé, `HomeController` interroge désormais
   `MembreEquipe::where('statut', 'ACTIF')->with('user')` et expose
   nom + rôle de chaque membre actif. Les photos de la galerie de carrousel
   restent des photos de stock génériques (la table `membres_equipe` n'a pas
   de colonne photo), associées par index.
5. **Témoignages** — contenu fixe (aucune table « avis » dans le cahier des
   charges), avec étoiles (`lucide-react` `Star`), réplique le texte exact de
   la maquette pour le premier témoignage (Aïcha N.).
6. **Formules mensuelles** — **déplacé depuis une bannière vers un affichage
   direct** des formules publiées (même requête que `FormuleController`,
   désormais dupliquée dans `HomeController`), réordonnées pour que la
   formule `populaire` apparaisse au centre de la grille de 3
   (`reordonnerFormules()`), comme dans la maquette.
7. **Bannière CTA finale** — remplacée par 3 boutons de contact direct
   (WhatsApp / Téléphone / E-mail), **sans formulaire** — conforme à la
   maquette, qui n'affiche pas de champ de saisie à cet endroit.

Composants partagés ajustés en même temps :

- `topbar.tsx` : libellé « Services » (au lieu de « Catalogue »), ajout du
  lien « À propos » (ancre `/#equipe`, aucune page dédiée dans la maquette
  fournie), libellés de boutons « Connexion » / « Démarrer un projet ».
- `site-footer.tsx` : colonnes « Services / Espaces / Contact » avec les
  libellés exacts de la maquette (dont « Offres de stage & emploi » →
  nouvelle page, et « Entreprise partenaire » → page contact, puisque ce
  compte n'a plus d'espace propre depuis le retrait documenté dans
  `02-phase-2-...md`), 5 icônes sociales (`lucide-react`), 4 liens légaux en
  pied de page (Mentions légales / Confidentialité / CGU / CGV).
- `contact-fab.tsx` : coordonnées réelles (`+237 6 99 00 00 00`,
  `contact@dag.cm`), titre « Contactez DAG ».

## 3. Formulaires sans compte

Deux actions ne nécessitent aucune authentification, conformément à la
section 6.1 :

- `POST /contact` (`contact.store`) → `ContactController@store` crée un
  `ContactMessage` (statut initial `NOUVEAU`). Formulaire sur `/contact`.
- `POST /audit-gratuit` (`audit-requests.store`) →
  `AuditRequestController@store` crée un `AuditRequest` (statut initial
  `NOUVELLE`). Le bouton « Audit gratuit » de la bannière CTA de l'accueil
  (`public/home.tsx`) ouvre une modale dédiée
  (`components/public/audit-request-modal.tsx`) avec les champs URL de la
  page + e-mail.

Les deux modèles (`app/Models/ContactMessage.php`, `app/Models/AuditRequest.php`)
sont des tables de capture simples, sans relation : leur traitement (passage à
`TRAITE`/`TRAITEE`) sera piloté depuis l'espace administrateur (phase 10).

## 4. Tests (`tests/Feature/Public/PublicPagesTest.php`)

11 tests couvrant :

- l'accueil expose le compteur de services publiés, l'équipe active et les
  formules publiées ;
- le catalogue exclut les services en brouillon ;
- la liste des formations n'inclut que les sessions `OUVERTE`/`COMPLETE`
  (pas `BROUILLON`/`TERMINEE`) ;
- la fiche formation expose ses sessions ouvertes ;
- l'abonnement trie la formule populaire en premier et exclut les brouillons ;
- la page contact s'affiche ;
- la soumission du formulaire de contact crée bien un `ContactMessage`, et
  rejette les entrées invalides (validation) ;
- la soumission de l'audit gratuit crée bien un `AuditRequest`, et rejette les
  entrées invalides ;
- les offres de stage & emploi n'affichent que les offres publiées et non
  expirées.

Une factory `FormuleFactory` a été ajoutée (le modèle `Formule` n'avait pas
encore de factory ni le trait `HasFactory`).

## 5. Validation exécutée

- `php artisan test --compact` : **142/142** tests verts.
- `vendor/bin/phpstan analyse --memory-limit=1G` : 0 erreur (après ajout de
  docblocks `@property Carbon|null` sur `FormationSession::$date_debut/$date_fin`
  et `OffreEmploi::$date_limite` — Larastan n'inférait pas correctement le
  cast `'date'` déclaré via la méthode `casts()` dans ce contexte précis, et
  refactorisation des contrôleurs de `map()/flatMap()` chaînés vers des
  boucles `foreach` explicites, plus lisibles pour l'analyse statique comme
  pour la relecture).
- `vendor/bin/pint app database tests --format agent` : formatage appliqué.
- `npm run build` : build Vite réussi, polices Sora/Plus Jakarta Sans
  présentes dans le manifeste.
- `npx vp check --fix` : 0 avertissement/erreur de lint sur 91 fichiers.
- `tsc --noEmit` : 0 erreur de typage.
- Vérification manuelle (serveur local) : le payload Inertia de `/` confirme
  les vraies données (17 services, 5 membres d'équipe actifs avec leur rôle
  réel, 3 formules dont la populaire au centre après tri).

> **Piège rencontré** : régénérer les routes Wayfinder en ligne de commande
> sans l'option `--with-form` (`php artisan wayfinder:generate --no-interaction`)
> écrase les fichiers générés et retire les méthodes `.form()` utilisées par
> plusieurs pages du starter kit (login, register, settings, teams...),
> cassant `tsc --noEmit` sur des fichiers non liés à cette phase. Le projet
> génère normalement ces routes avec `formVariants: true` via le plugin Vite
> (`vite.config.ts`) — toute régénération manuelle doit donc utiliser
> `php artisan wayfinder:generate --with-form --no-interaction`.

## 6. Hors périmètre (volontairement)

- i18n réelle (le sélecteur FR/EN est un gabarit visuel, non fonctionnel).
- Inscription effective à une session de formation, et candidature à une
  offre de stage/emploi (nécessitent un compte Apprenant authentifié →
  phase 7).
- Traitement des `ContactMessage`/`AuditRequest` côté équipe DAG → phase 10
  (espace administrateur).
