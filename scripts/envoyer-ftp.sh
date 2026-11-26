#!/usr/bin/env bash
#
# Envoie par FTPS la version courante (HEAD) sur le serveur. Lancé par
# GitHub Actions (.github/workflows/deploiement.yml), jamais à la main.
#
#  - Premier envoi : tous les fichiers du dépôt.
#  - Ensuite : seulement ceux modifiés depuis la version déjà sur le serveur
#    (.deploiement/version), et suppression de ceux retirés du dépôt.
#  - public/build (le front construit par GitHub) est remis à l'identique.
#  - Jamais envoyés : .env, vendor/, node_modules/, tests/, .github/. Rien
#    d'autre n'est supprimé sur le serveur (storage/, .env, vendor/ restent).
#  - En dernier, .deploiement/version reçoit le commit envoyé : le cron du
#    serveur (scripts/apres-deploiement.sh) voit alors qu'une version est
#    arrivée et termine la mise en ligne. Si l'envoi échoue avant, la
#    version n'est pas écrite et le prochain envoi reprend depuis l'ancienne.
#
# Variables : FTP_SERVEUR, FTP_UTILISATEUR, LFTP_PASSWORD (mot de passe),
# FTP_DOSSIER, FTP_VERIFIER_CERTIFICAT (yes/no). ENVOI_A_BLANC=1 affiche ce
# qui serait envoyé sans se connecter (ENVOI_DEPUIS=<commit> : depuis ce
# commit, comme si le serveur l'avait).

set -euo pipefail

A_BLANC="${ENVOI_A_BLANC:-}"

for variable in FTP_SERVEUR FTP_UTILISATEUR LFTP_PASSWORD FTP_DOSSIER; do
    if [ -z "${!variable:-}" ] && [ -z "$A_BLANC" ]; then
        echo "::error::$variable n'est pas réglé dans GitHub (Settings > Environments > production)."
        exit 1
    fi
done

SITE_URL="${SITE_URL:-https://agencydag.com}"
DOSSIER="${FTP_DOSSIER:-htdocs}"
DOSSIER="${DOSSIER%/}"
VERSION="$(git rev-parse HEAD)"
TRAVAIL="$(mktemp -d)"
trap 'rm -rf "$TRAVAIL"' EXIT

# Paths of the repository that never go to the server.
exclus() {
    grep -zvE '^(tests/|\.github/|\.ai/|\.claude/)|(^|/)\.env$' || true
}

# Commands opening the FTPS connection; the password comes from LFTP_PASSWORD
# so it never appears in a command line or a log.
entete() {
    cat <<EOF
set cmd:fail-exit yes
set net:timeout 30
set net:max-retries 3
set net:reconnect-interval-base 5
set ftp:passive-mode yes
set ftp:ssl-force yes
set ftp:ssl-protect-data yes
set ssl:verify-certificate ${FTP_VERIFIER_CERTIFICAT:-yes}
open --env-password -u "${FTP_UTILISATEUR:-}" "ftp://${FTP_SERVEUR:-}"
EOF
}

# Quotes a path for an lftp command.
chemin() {
    printf '"%s"' "${1//\"/\\\"}"
}

# The application folder must not be the public root of the site: the domain
# serves its public/ subfolder, otherwise .env and storage/ could be
# downloaded. A random file is put at the top of the folder and asked from
# the site: if the site returns it, or cannot be reached to tell, nothing is
# sent. VERIFIER_EXPOSITION=no skips it (only once the server is known).
verifier_exposition() {
    if [ "${VERIFIER_EXPOSITION:-yes}" = "no" ]; then
        echo "::warning::Vérification de l'exposition du dossier désactivée (VERIFIER_EXPOSITION=no)."
        return 0
    fi

    local jeton code
    jeton="sonde-$(openssl rand -hex 16)"
    printf '%s' "$jeton" > "$TRAVAIL/sonde"

    # When the FTP folder is the public root of the site (shared hosting),
    # the root .htaccess is what keeps the folder private (everything served
    # from public/): it goes first, and the probe then checks it works.
    {
        entete
        if [ -f .htaccess ]; then
            echo "put $(chemin ".htaccess") -o $(chemin "$DOSSIER/.htaccess")"
        fi
        echo "put $(chemin "$TRAVAIL/sonde") -o $(chemin "$DOSSIER/$jeton.txt")"
    } > "$TRAVAIL/sonde.lftp"
    lftp -f "$TRAVAIL/sonde.lftp"

    code="$(curl -sS -L -o "$TRAVAIL/reponse" -w '%{http_code}' --max-time 20 "${SITE_URL%/}/$jeton.txt" 2>/dev/null || true)"

    { entete; echo "rm -f $(chemin "$DOSSIER/$jeton.txt")"; } > "$TRAVAIL/nettoyage.lftp"
    lftp -f "$TRAVAIL/nettoyage.lftp" || true

    if grep -q "$jeton" "$TRAVAIL/reponse" 2>/dev/null; then
        echo "::error::Le dossier $DOSSIER est visible depuis Internet ($SITE_URL) : .env et storage/ y seraient téléchargeables. Faites pointer le domaine sur $DOSSIER/public, puis relancez. Rien n'a été envoyé."
        exit 1
    fi

    if [ -z "$code" ] || [ "$code" = "000" ]; then
        echo "::error::Le site $SITE_URL ne répond pas (HTTPS) : impossible de vérifier que $DOSSIER n'est pas public. Rien n'a été envoyé."
        exit 1
    fi

    echo "Le dossier $DOSSIER n'est pas public (le site répond $code au fichier témoin) : envoi."
}

# The version already on the server, empty for a first deployment.
ANCIENNE="${ENVOI_DEPUIS:-}"
if [ -z "$A_BLANC" ]; then
    { entete; echo "cat $(chemin "$DOSSIER/.deploiement/version")"; } > "$TRAVAIL/lire.lftp"
    ANCIENNE="$(lftp -f "$TRAVAIL/lire.lftp" 2>/dev/null | tr -d '[:space:]' || true)"
fi

if [ -n "$ANCIENNE" ] && ! git cat-file -e "${ANCIENNE}^{commit}" 2>/dev/null; then
    echo "La version du serveur ($ANCIENNE) est inconnue du dépôt : envoi complet."
    ANCIENNE=""
fi

if [ "$ANCIENNE" = "$VERSION" ]; then
    echo "Le serveur a déjà la version $VERSION : rien à envoyer."
    exit 0
fi

# Files to send and files to remove.
if [ -z "$ANCIENNE" ]; then
    echo "Premier envoi (ou envoi complet) de la version $VERSION."
    git ls-files -z | exclus > "$TRAVAIL/a-envoyer"
    : > "$TRAVAIL/a-supprimer"
else
    echo "Envoi des changements de $ANCIENNE à $VERSION."
    git diff --no-renames --name-only -z --diff-filter=ACMRT "$ANCIENNE" "$VERSION" | exclus > "$TRAVAIL/a-envoyer"
    git diff --no-renames --name-only -z --diff-filter=D "$ANCIENNE" "$VERSION" | exclus > "$TRAVAIL/a-supprimer"
fi

mkdir -p "$TRAVAIL/envoi"
if [ -s "$TRAVAIL/a-envoyer" ]; then
    tar --null -T "$TRAVAIL/a-envoyer" -cf - | tar -xf - -C "$TRAVAIL/envoi"
fi

printf '%s\n' "$VERSION" > "$TRAVAIL/version"

{
    entete
    if [ -s "$TRAVAIL/a-envoyer" ]; then
        echo "mirror -R --no-perms --parallel=4 $(chemin "$TRAVAIL/envoi/") $(chemin "$DOSSIER/")"
    fi
    while IFS= read -r -d '' fichier; do
        echo "rm -f $(chemin "$DOSSIER/$fichier")"
    done < "$TRAVAIL/a-supprimer"
    echo "mkdir -p -f $(chemin "$DOSSIER/public/build")"
    echo "mirror -R --delete --no-perms --parallel=4 $(chemin "public/build/") $(chemin "$DOSSIER/public/build/")"
    echo "mkdir -p -f $(chemin "$DOSSIER/.deploiement")"
    echo "put $(chemin "$TRAVAIL/version") -o $(chemin "$DOSSIER/.deploiement/version")"
} > "$TRAVAIL/envoi.lftp"

ENVOYES="$(tr -cd '\0' < "$TRAVAIL/a-envoyer" | wc -c)"
SUPPRIMES="$(tr -cd '\0' < "$TRAVAIL/a-supprimer" | wc -c)"
echo "$ENVOYES fichier(s) à envoyer, $SUPPRIMES à supprimer, plus public/build."

if [ -n "$A_BLANC" ]; then
    echo "--- Envoi à blanc : commandes qui seraient lancées (hors connexion) ---"
    grep -v '^set \|^open ' "$TRAVAIL/envoi.lftp" | sed "s#$TRAVAIL#<travail>#g" | head -40
    exit 0
fi

verifier_exposition
lftp -f "$TRAVAIL/envoi.lftp"
echo "Version $VERSION envoyée : le serveur la met en ligne dans la minute (scripts/apres-deploiement.sh)."
