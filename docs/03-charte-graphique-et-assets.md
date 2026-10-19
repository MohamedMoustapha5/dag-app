# Charte graphique & assets de marque

Référence pour l'habillage visuel réel des pages (phase 3 et suivantes).

## 1. Décision typographique

La charte graphique officielle (PDF, octobre 2026) recommande **Poppins** (police
principale) + **Inter** (police secondaire). Les maquettes HTML fournies chargent en
réalité **Sora** + **Plus Jakarta Sans**. Après validation avec le porteur de projet :
**on garde Sora + Plus Jakarta Sans**, conformément aux maquettes déjà approuvées —
l'écart visuel avec Poppins/Inter est faible (polices géométriques similaires).

## 2. Palette de couleurs

Confirmée par la charte graphique (section « Palette de couleurs ») :

| Nom                 | Hex       | Usage                                                   |
| ------------------- | --------- | ------------------------------------------------------- |
| Bleu marine DAG     | `#071F41` | Logo, titres, textes — 20-30 % de la composition        |
| Bleu électrique DAG | `#0166FE` | Digital, boutons, accents — 10-20 %                     |
| Orange signal       | `#FAA608` | Accent, CTA ponctuel — 3-5 % seulement, jamais dominant |
| Blanc               | `#FEFEFE` | Fond principal, respiration — 55-65 %                   |

À vérifier/aligner avec `assets/style.css` des maquettes au moment de construire les
vraies pages (phase 3) : les variables CSS `--navy`, `--blue`, `--orange` doivent
correspondre à ces codes exacts.

## 3. Logo

Fichier reçu : `public/images/brand/dag-logo.jpg` (1080×1080, fond blanc). Règles de
la charte à respecter dans l'intégration :

- Zone de protection : aucun texte/élément ne doit entrer dans une marge égale à la
  hauteur du « A » du monogramme, tout autour du logo.
- Taille minimale : ~120 px de large sur le web.
- Ne jamais : étirer, écraser, recolorer, incliner, ombrer, séparer le monogramme de
  la signature « Digital Agency Group », ou le poser sur un fond peu contrasté /
  une image chargée sans zone de contraste.
- Le logo fourni est au format JPEG avec fond blanc. **Un PNG avec fond transparent
  sera nécessaire** pour les usages sur fond bleu marine (ex. topbar sombre,
  footer) — à redemander ou à détourer avant la phase 3 si besoin.

## 4. Photothèque

41 photos de mise en situation reçues, enregistrées dans `public/images/stock/`
(`photo-07.jpg` à `photo-47.jpg`). Elles correspondent aux visuels utilisés dans les
maquettes HTML (`assets/photos/*.jpg`, `assets/team/*.jpg`) : bureaux/laptops,
code/écrans, poignées de main business, bâtiments de bureaux, réunions, portraits
d'équipe, réseaux sociaux. Le mappage précis « quelle photo pour quel bloc de quelle
page » se fera au moment de construire chaque page en phase 3+, en choisissant parmi
ce lot celle qui correspond le mieux au contexte (service, formation, témoignage...).

## 5. Rappel — langage graphique

- Formes géométriques simples (cercles, arcs, rectangles à coins légèrement
  arrondis). Le motif d'ondes orange (déjà présent dans le logo) peut servir
  d'élément secondaire discret (séparateur, coin de visuel) mais ne doit jamais
  concurrencer le monogramme.
- Icônes linéaires ou semi-pleines, trait homogène, jamais réalistes/multicolores.
- Ton rédactionnel : professionnel, clair, direct, moderne, positif, orienté
  solutions — cohérent avec le français déjà utilisé dans les maquettes.
