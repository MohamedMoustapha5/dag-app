#!/bin/sh
#
# Met en ligne, sur le serveur, la version que GitHub vient d'envoyer par FTP
# (scripts/envoyer-ftp.sh). Lancé chaque minute par scripts/cron.sh, l'unique
# tâche cron de l'hébergement (la ligne à mettre dans le panneau y est) ; il
# ne fait rien tant qu'aucune nouvelle version n'est arrivée.
#
# À chaque nouvelle version : composer install, migrate, cache, redémarrage
# de la file d'attente. À la toute première : aussi db:seed (comptes de
# l'équipe, accès envoyés par e-mail), une seule fois. Quand le .env change,
# le cache de configuration est refait.
#
# Suivi, lisible par FTP : .deploiement/statut (où on en est) et
# storage/logs/deploiement.log (le détail). Après un échec, la version n'est
# plus retentée : corrigez la cause, puis supprimez .deploiement/echec.

set -eu
cd "$(dirname "$0")/.."

PHP=${PHP:-php}
COMPOSER=${COMPOSER:-composer}
ETAT=.deploiement

journal() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"
}

statut() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') — $*" > "$ETAT/statut"
}

[ -f "$ETAT/version" ] || exit 0

if [ ! -f .env ]; then
    statut "En attente du fichier .env : créez-le à côté du fichier artisan."
    exit 0
fi

VERSION=$(cat "$ETAT/version")
APPLIQUEE=$(cat "$ETAT/appliquee" 2>/dev/null || true)
EMPREINTE_ENV=$(cksum < .env)

if [ "$VERSION" = "$APPLIQUEE" ] && [ "$EMPREINTE_ENV" = "$(cat "$ETAT/env" 2>/dev/null || true)" ]; then
    exit 0
fi

if [ "$VERSION" != "$APPLIQUEE" ] && [ "$VERSION" = "$(cat "$ETAT/echec" 2>/dev/null || true)" ]; then
    exit 0
fi

# One run at a time; a lock older than 30 minutes is a run that was killed.
find "$ETAT" -maxdepth 1 -name verrou -mmin +30 -exec rmdir {} \; 2>/dev/null || true
mkdir "$ETAT/verrou" 2>/dev/null || exit 0

EN_MAINTENANCE=""

terminer() {
    code=$?
    if [ "$EN_MAINTENANCE" = 1 ]; then
        $PHP artisan up > /dev/null 2>&1 || true
    fi
    if [ "$code" -ne 0 ]; then
        echo "$VERSION" > "$ETAT/echec"
        statut "ÉCHEC de la version $VERSION : voir storage/logs/deploiement.log, puis supprimer .deploiement/echec pour réessayer."
        journal "ÉCHEC (code $code)"
    fi
    rmdir "$ETAT/verrou" 2>/dev/null || true
}
trap terminer EXIT

# On shared hosting the site and the terminal do not see the same paths (the
# site sees /htdocs, the terminal /home/…/htdocs). A configuration or view
# cache built here would hold paths the site cannot open: only the routes
# and events, which hold no path, are cached.
mettre_en_cache() {
    $PHP artisan optimize:clear > /dev/null
    $PHP artisan route:cache
    $PHP artisan event:cache
}

if [ "$VERSION" = "$APPLIQUEE" ]; then
    journal "Le .env a changé : caches refaits."
    statut "Prise en compte du nouveau .env…"
    mettre_en_cache
    $PHP artisan queue:restart
    echo "$EMPREINTE_ENV" > "$ETAT/env"
    statut "En ligne : version $VERSION (.env pris en compte)."
    exit 0
fi

journal "Mise en ligne de la version $VERSION"
statut "Mise en ligne de la version $VERSION…"

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views \
    storage/logs storage/app/public storage/app/private bootstrap/cache

if [ -f vendor/autoload.php ] && $PHP artisan down --retry=15; then
    EN_MAINTENANCE=1
fi

$COMPOSER install --no-dev --optimize-autoloader --no-interaction --no-progress

if ! grep -q '^APP_KEY=base64:' .env; then
    $PHP artisan key:generate --force
fi

$PHP artisan migrate --force

# The first deployment only: the reference data and the team, whose members
# each receive their access by e-mail (Brevo must already be in the .env).
if [ ! -f "$ETAT/seed-fait" ]; then
    journal "Premier déploiement : données de référence et comptes de l'équipe"
    $PHP artisan db:seed --force
    date '+%Y-%m-%d %H:%M:%S' > "$ETAT/seed-fait"
fi

# The public files (partner logos, images of services and formations) are
# served from public/storage. A relative link, so the site reaches it with
# its own paths (artisan storage:link would write the terminal's ones).
if [ ! -e public/storage ]; then
    ln -s ../storage/app/public public/storage \
        || journal "Lien public/storage impossible : les logos et images ne s'afficheront pas, voir avec l'hébergeur."
fi

mettre_en_cache
$PHP artisan queue:restart

echo "$VERSION" > "$ETAT/appliquee"
cksum < .env > "$ETAT/env"
rm -f "$ETAT/echec"
statut "En ligne : version $VERSION."
journal "Version $VERSION en ligne"
