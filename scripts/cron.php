<?php

/**
 * The one cron task of the hosting, every minute:
 *
 *     php /home/htdocs/scripts/cron.php
 *
 * Written in PHP rather than shell: the hosting's shell lacks basic commands
 * (dirname…), PHP is always there. Each run, in turn:
 *
 *  1. Puts online a version GitHub has just sent by FTP (.deploiement/version):
 *     composer install, migrate, the seed on the very first one only (team
 *     accounts, each member e-mailed their access), the public/storage link,
 *     route and event caches. When only the .env changed, the caches are
 *     redone. Progress in .deploiement/statut, detail in
 *     storage/logs/deploiement.log. After a failure the version is not
 *     retried: fix the cause, then delete .deploiement/echec.
 *  2. Runs the scheduled tasks (Fapshi payments still pending, reminders).
 *  3. Sends what waits in the queue (e-mails, PDF), then stops before the next
 *     minute.
 *
 * Environment variables, all optional: PHP (another PHP binary for artisan),
 * COMPOSER (the composer command, e.g. "php /home/composer.phar").
 */
chdir(dirname(__DIR__));

const ETAT = '.deploiement';

$php = getenv('PHP') ?: PHP_BINARY;

/** Writes a line to the deployment log. */
function journal(string $texte): void
{
    @mkdir('storage/logs', 0775, true);
    file_put_contents('storage/logs/deploiement.log', '['.date('Y-m-d H:i:s')."] {$texte}\n", FILE_APPEND);
}

/** Where things stand, readable by FTP. */
function statut(string $texte): void
{
    file_put_contents(ETAT.'/statut', date('Y-m-d H:i:s')." — {$texte}\n");
}

/** Runs a command, its output going to the log; throws when it fails. */
function lancer(string $commande): void
{
    journal("> {$commande}");
    $sortie = [];
    exec($commande.' 2>&1', $sortie, $code);
    if ($sortie !== []) {
        file_put_contents('storage/logs/deploiement.log', implode("\n", $sortie)."\n", FILE_APPEND);
    }
    if ($code !== 0) {
        throw new RuntimeException("« {$commande} » a échoué (code {$code}).");
    }
}

function artisan(string $arguments): string
{
    global $php;

    return escapeshellarg($php).' artisan '.$arguments;
}

/** The composer command: COMPOSER, else composer in the PATH, else a composer.phar nearby. */
function composer(): string
{
    global $php;

    if (getenv('COMPOSER')) {
        return getenv('COMPOSER');
    }

    exec('command -v composer 2>/dev/null', $trouve, $code);
    if ($code === 0 && $trouve !== []) {
        return 'composer';
    }

    foreach (['composer.phar', '../composer.phar', (getenv('HOME') ?: '').'/composer.phar'] as $phar) {
        if (is_file($phar)) {
            return escapeshellarg($php).' '.escapeshellarg($phar);
        }
    }

    throw new RuntimeException('Composer introuvable : installez-le ou indiquez son chemin (variable COMPOSER).');
}

/**
 * Only the routes and events are cached: the site and the terminal do not see
 * the same paths on this hosting (/htdocs against /home/htdocs), so a cached
 * configuration or view would hold paths the site cannot open.
 */
function mettreEnCache(): void
{
    lancer(artisan('optimize:clear'));
    lancer(artisan('route:cache'));
    lancer(artisan('event:cache'));
}

function deployer(): void
{
    if (! is_file(ETAT.'/version')) {
        return;
    }

    if (! is_file('.env')) {
        statut('En attente du fichier .env : créez-le à côté du fichier artisan.');

        return;
    }

    $version = trim((string) file_get_contents(ETAT.'/version'));
    $appliquee = is_file(ETAT.'/appliquee') ? trim((string) file_get_contents(ETAT.'/appliquee')) : '';
    $echec = is_file(ETAT.'/echec') ? trim((string) file_get_contents(ETAT.'/echec')) : '';
    $empreinteEnv = (string) md5_file('.env');
    $envConnu = is_file(ETAT.'/env') ? trim((string) file_get_contents(ETAT.'/env')) : '';

    if ($version === $appliquee && $empreinteEnv === $envConnu) {
        return;
    }

    if ($version !== $appliquee && $version === $echec) {
        return;
    }

    // One run at a time; a lock older than 30 minutes is a run that was killed.
    $verrou = ETAT.'/verrou';
    if (is_dir($verrou) && filemtime($verrou) < time() - 1800) {
        @rmdir($verrou);
    }
    if (! @mkdir($verrou)) {
        return;
    }

    $enMaintenance = false;

    try {
        if ($version === $appliquee) {
            journal('Le .env a changé : caches refaits.');
            statut('Prise en compte du nouveau .env…');
            mettreEnCache();
            lancer(artisan('queue:restart'));
            file_put_contents(ETAT.'/env', $empreinteEnv);
            statut("En ligne : version {$version} (.env pris en compte).");

            return;
        }

        journal("Mise en ligne de la version {$version}");
        statut("Mise en ligne de la version {$version}…");

        foreach (['storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views',
            'storage/logs', 'storage/app/public', 'storage/app/private', 'bootstrap/cache'] as $dossier) {
            @mkdir($dossier, 0775, true);
        }

        if (is_file('vendor/autoload.php')) {
            try {
                lancer(artisan('down --retry=15'));
                $enMaintenance = true;
            } catch (RuntimeException) {
                // Not fatal: the site just stays open during the update.
            }
        }

        lancer(composer().' install --no-dev --optimize-autoloader --no-interaction --no-progress');

        if (! preg_match('/^APP_KEY=base64:/m', (string) file_get_contents('.env'))) {
            lancer(artisan('key:generate --force'));
        }

        lancer(artisan('migrate --force'));

        // The very first deployment only: reference data and the team, each
        // member receiving their access by e-mail (Brevo must be in the .env).
        if (! is_file(ETAT.'/seed-fait')) {
            journal("Premier déploiement : données de référence et comptes de l'équipe");
            lancer(artisan('db:seed --force'));
            file_put_contents(ETAT.'/seed-fait', date('Y-m-d H:i:s'));
        }

        // Public files (partner logos, images) are served from public/storage:
        // a relative link, valid for the site and the terminal alike.
        if (! file_exists('public/storage') && ! is_link('public/storage')) {
            if (! @symlink('../storage/app/public', 'public/storage')) {
                journal("Lien public/storage impossible : les logos et images ne s'afficheront pas, voir avec l'hébergeur.");
            }
        }

        mettreEnCache();
        lancer(artisan('queue:restart'));

        file_put_contents(ETAT.'/appliquee', $version);
        file_put_contents(ETAT.'/env', (string) md5_file('.env'));
        @unlink(ETAT.'/echec');
        statut("En ligne : version {$version}.");
        journal("Version {$version} en ligne");
    } catch (Throwable $erreur) {
        if ($version !== $appliquee) {
            file_put_contents(ETAT.'/echec', $version);
        }
        statut("ÉCHEC de la version {$version} : {$erreur->getMessage()} Voir storage/logs/deploiement.log, puis supprimer .deploiement/echec pour réessayer.");
        journal('ÉCHEC : '.$erreur->getMessage());
    } finally {
        if ($enMaintenance) {
            try {
                lancer(artisan('up'));
            } catch (RuntimeException) {
                journal("Le site n'a pas pu sortir de maintenance : lancez « php artisan up ».");
            }
        }
        @rmdir($verrou);
    }
}

deployer();

// Not installed yet (first deployment waiting for its .env): nothing to run.
if (! is_file('vendor/autoload.php') || ! is_file('.env')) {
    exit(0);
}

exec(artisan('schedule:run').' > /dev/null 2>&1');
exec(artisan('queue:work --stop-when-empty --max-time=50 --tries=3').' > /dev/null 2>&1');
