<#
.SYNOPSIS
    Envoie le .env de production sur le serveur, seul et chiffré (FTPS).

.DESCRIPTION
    Le .env de production est le fichier .env.production, à la racine du
    projet, sur ce PC. Il est distinct du .env de développement et n'est
    jamais dans git (.gitignore) ni dans GitHub.

    Avant l'envoi, le fichier est vérifié : un réglage dangereux en
    production (APP_DEBUG=true, clé vide, adresse sans https…) l'arrête.
    Sur le serveur, l'ancien .env est gardé en .env.precedent ; les droits
    des fichiers ne changent pas (l'application et le compte FTP les lisent),
    et le .htaccess racine les rend inaccessibles depuis Internet. Le cron du
    serveur voit le changement et refait le cache dans la minute.

    Les identifiants FTP sont demandés une seule fois et rangés chiffrés par
    Windows (DPAPI) dans %APPDATA%\dag-app : seule cette session Windows
    peut les relire.

    Le hook git .githooks/pre-push le lance avec -SiChange à chaque push :
    le fichier ne part que s'il a changé depuis le dernier envoi.

.EXAMPLE
    pwsh scripts/envoyer-env.ps1
    Vérifie et envoie .env.production.

.EXAMPLE
    pwsh scripts/envoyer-env.ps1 -ABlanc
    Vérifie le fichier et dit ce qui serait fait, sans se connecter.

.EXAMPLE
    pwsh scripts/envoyer-env.ps1 -ChangerIdentifiants
    Redemande l'identifiant et le mot de passe FTP (après un changement).
#>
[CmdletBinding()]
param(
    [switch] $SiChange,
    [switch] $ABlanc,
    [switch] $ChangerIdentifiants,
    [string] $Serveur = 'webdb2220.lwspanel.com',
    [string] $Dossier = '/'
)

$ErrorActionPreference = 'Stop'

$racine = Split-Path -Parent $PSScriptRoot
$fichier = Join-Path $racine '.env.production'
$dossierLocal = Join-Path $env:APPDATA 'dag-app'
$fichierIdentifiants = Join-Path $dossierLocal 'ftp-production.xml'
$fichierEmpreinte = Join-Path $dossierLocal 'env-production.sha256'

function Write-Etape([string] $texte) { Write-Host "  $texte" }

if (-not (Test-Path -LiteralPath $fichier)) {
    if ($SiChange) { exit 0 }
    throw "Fichier introuvable : $fichier. Créez-le à partir de .env.example avec les réglages de production."
}

# --- Vérification du contenu ------------------------------------------------

$lignes = Get-Content -LiteralPath $fichier -Encoding UTF8

function Get-Valeur([string] $cle) {
    $ligne = $lignes | Where-Object { $_ -match "^\s*$cle\s*=" } | Select-Object -Last 1
    if ($null -eq $ligne) { return '' }
    return (($ligne -replace "^\s*$cle\s*=\s*", '').Trim() -replace '^"(.*)"$', '$1' -replace "^'(.*)'$", '$1')
}

$erreurs = @()
$avertissements = @()

if ((Get-Valeur 'APP_ENV') -ne 'production') { $erreurs += 'APP_ENV doit valoir production.' }
if ((Get-Valeur 'APP_DEBUG') -ne 'false') { $erreurs += 'APP_DEBUG doit valoir false (sinon les erreurs montrent le code et les clés à tout le monde).' }
if ((Get-Valeur 'APP_KEY') -notmatch '^base64:.{40,}$') { $erreurs += 'APP_KEY est vide ou invalide : générez-la avec « php artisan key:generate --show » et collez-la (une seule fois, ne plus jamais la changer).' }
if ((Get-Valeur 'APP_URL') -notmatch '^https://') { $erreurs += 'APP_URL doit commencer par https://.' }
foreach ($cle in 'DB_CONNECTION', 'DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD') {
    if ((Get-Valeur $cle) -eq '') { $erreurs += "$cle est vide." }
}
if ((Get-Valeur 'MAIL_MAILER') -in @('', 'log', 'array')) { $avertissements += 'MAIL_MAILER n''envoie pas de vrais e-mails (log/array) : les accès de l''équipe ne partiront pas.' }
if ((Get-Valeur 'MAIL_MAILER') -eq 'brevo' -and (Get-Valeur 'BREVO_API_KEY') -eq '') { $erreurs += 'MAIL_MAILER=brevo mais BREVO_API_KEY est vide.' }
if ((Get-Valeur 'PAIEMENT_PASSERELLE') -eq 'simulation') { $avertissements += 'PAIEMENT_PASSERELLE=simulation : aucun vrai paiement ne sera possible.' }

if ($avertissements.Count -gt 0) {
    Write-Host 'Attention :' -ForegroundColor Yellow
    $avertissements | ForEach-Object { Write-Host "  - $_" -ForegroundColor Yellow }
}

if ($erreurs.Count -gt 0) {
    Write-Host '.env.production n''est pas envoyé :' -ForegroundColor Red
    $erreurs | ForEach-Object { Write-Host "  - $_" -ForegroundColor Red }
    exit 1
}

# --- A-t-il changé ? --------------------------------------------------------

$empreinte = (Get-FileHash -LiteralPath $fichier -Algorithm SHA256).Hash
$derniere = if (Test-Path -LiteralPath $fichierEmpreinte) { (Get-Content -LiteralPath $fichierEmpreinte -Raw).Trim() } else { '' }

if ($SiChange -and $empreinte -eq $derniere) {
    exit 0
}

$chemin = $Dossier.TrimEnd('/')
$url = "ftp://$Serveur$chemin/.env.nouveau"

if ($ABlanc) {
    Write-Host '.env.production est valide. Envoi à blanc, rien n''est envoyé :' -ForegroundColor Green
    Write-Etape "envoi chiffré (FTPS) vers $url"
    Write-Etape 'sur le serveur : .env -> .env.precedent, .env.nouveau -> .env (droits inchangés)'
    Write-Etape ($(if ($empreinte -eq $derniere) { 'le fichier n''a pas changé depuis le dernier envoi' } else { 'le fichier a changé depuis le dernier envoi' }))
    exit 0
}

# --- Identifiants FTP, chiffrés par Windows ----------------------------------

if ($ChangerIdentifiants -or -not (Test-Path -LiteralPath $fichierIdentifiants)) {
    if (-not [Environment]::UserInteractive -or $SiChange) {
        Write-Host 'Identifiants FTP pas encore enregistrés : lancez une fois « pwsh scripts/envoyer-env.ps1 » dans un terminal.' -ForegroundColor Yellow
        exit 0
    }
    New-Item -ItemType Directory -Force -Path $dossierLocal | Out-Null
    $saisie = Get-Credential -Message "Identifiant et mot de passe FTP de $Serveur"
    if ($null -eq $saisie) { throw 'Aucun identifiant saisi.' }
    $saisie | Export-Clixml -LiteralPath $fichierIdentifiants
}

$identifiants = Import-Clixml -LiteralPath $fichierIdentifiants
$utilisateur = $identifiants.UserName
$motDePasse = $identifiants.GetNetworkCredential().Password

# --- Envoi ------------------------------------------------------------------

# The credentials reach curl through its standard input (--config -): they
# never appear on a command line nor in a file.
$echappe = ("{0}:{1}" -f $utilisateur, $motDePasse).Replace('\', '\\').Replace('"', '\"')
$configuration = "user = `"$echappe`""

$commandes = @(
    '-*DELE .env.precedent',
    '-*RNFR .env',
    '-*RNTO .env.precedent',
    '-RNFR .env.nouveau',
    '-RNTO .env'
)
$arguments = @('--silent', '--show-error', '--ssl-reqd', '--connect-timeout', '20', '--max-time', '120', '--config', '-', '--upload-file', $fichier, $url)
foreach ($commande in $commandes) { $arguments += @('-Q', $commande) }

Write-Host "Envoi de .env.production vers $Serveur (FTPS chiffré)…"
$configuration | & curl.exe @arguments
if ($LASTEXITCODE -ne 0) {
    throw "Envoi échoué (curl $LASTEXITCODE). Le .env du serveur n'a pas été remplacé si l'erreur vient avant le renommage."
}

New-Item -ItemType Directory -Force -Path $dossierLocal | Out-Null
Set-Content -LiteralPath $fichierEmpreinte -Value $empreinte -NoNewline

Write-Host '.env de production envoyé : le serveur refait son cache dans la minute. L''ancien est gardé en .env.precedent.' -ForegroundColor Green
