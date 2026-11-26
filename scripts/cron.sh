#!/bin/sh
#
# The one cron task of the hosting (it allows a single one), every minute:
#
#   * * * * * cd /chemin/vers/htdocs && sh scripts/cron.sh
#
# Each run, in turn:
#  1. puts online a version GitHub has just sent (scripts/apres-deploiement.sh,
#     detail in storage/logs/deploiement.log);
#  2. runs the scheduled tasks (Fapshi payments still pending, reminders);
#  3. sends what waits in the queue (e-mails, PDF), then stops before the
#     next minute.
#
# If « php » or « composer » are not the right ones on the server, prefix the
# line, for example: PHP=/usr/local/bin/php8.4 COMPOSER="php8.4 /home/moi/composer.phar" sh scripts/cron.sh

cd "$(dirname "$0")/.." || exit 1

PHP=${PHP:-php}
export PHP COMPOSER

mkdir -p storage/logs
sh scripts/apres-deploiement.sh >> storage/logs/deploiement.log 2>&1

# Not installed yet (first deployment waiting for its .env): nothing to run.
if [ ! -f vendor/autoload.php ] || [ ! -f .env ]; then
    exit 0
fi

$PHP artisan schedule:run > /dev/null 2>&1
$PHP artisan queue:work --stop-when-empty --max-time=50 --tries=3 > /dev/null 2>&1

exit 0
