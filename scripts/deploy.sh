#!/usr/bin/env bash
#
# Déploiement d'Aprosafe — hébergement mutualisé LWS (avec SSH) ou VPS.
#
#   ./scripts/deploy.sh                 déploiement complet
#   ./scripts/deploy.sh --sans-git      fichiers déjà envoyés par FTP
#   ./scripts/deploy.sh --sans-migrate  ne touche pas à la base
#   ./scripts/deploy.sh --dry-run       montre ce qui serait fait
#
# Le script s'arrête à la première erreur et remet l'application en ligne
# quoi qu'il arrive.

set -Eeuo pipefail

RACINE="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$RACINE"

GIT=1; MIGRATE=1; SIMULATION=0
BRANCHE="${BRANCHE:-main}"
DOSSIER_SAUVEGARDES="${DOSSIER_SAUVEGARDES:-$RACINE/storage/app/sauvegardes}"
PHP="${PHP:-php}"

for argument in "$@"; do
    case "$argument" in
        --sans-git) GIT=0 ;;
        --sans-migrate) MIGRATE=0 ;;
        --dry-run) SIMULATION=1 ;;
        *) echo "Option inconnue : $argument" >&2; exit 1 ;;
    esac
done

etape()   { printf '\n\033[1;36m==> %s\033[0m\n' "$1"; }
info()    { printf '    %s\n' "$1"; }
erreur()  { printf '\033[1;31m!! %s\033[0m\n' "$1" >&2; }

executer() {
    if [ "$SIMULATION" = "1" ]; then
        info "[simulation] $*"
    else
        "$@"
    fi
}

remettre_en_ligne() {
    if [ "$SIMULATION" = "0" ] && [ -f storage/framework/down ]; then
        $PHP artisan up || true
        info "Application remise en ligne."
    fi
}
trap remettre_en_ligne EXIT

# ------------------------------------------------------------------ Contrôles
etape "Contrôles préalables"
[ -f artisan ] || { erreur "artisan introuvable : lancez le script depuis la racine du projet."; exit 1; }
[ -f .env ]    || { erreur ".env introuvable."; exit 1; }

if grep -qE '^APP_DEBUG=true' .env; then
    erreur "APP_DEBUG=true dans .env : corrigez avant de déployer en production."
    exit 1
fi
info "PHP : $($PHP -v | head -n1)"
info "Branche : $BRANCHE"

# ------------------------------------------------------------------ Sauvegarde
etape "Sauvegarde de la base"
mkdir -p "$DOSSIER_SAUVEGARDES"
HORODATAGE="$(date +%Y%m%d-%H%M%S)"
FICHIER_SQL="$DOSSIER_SAUVEGARDES/base-$HORODATAGE.sql.gz"

lire_env() { grep -E "^$1=" .env | head -n1 | cut -d= -f2- | tr -d '"'"'"'' ; }
BASE="$(lire_env DB_DATABASE)"; UTILISATEUR="$(lire_env DB_USERNAME)"
MOTDEPASSE="$(lire_env DB_PASSWORD)"; HOTE="$(lire_env DB_HOST)"

if command -v mysqldump >/dev/null 2>&1; then
    if [ "$SIMULATION" = "1" ]; then
        info "[simulation] mysqldump vers $FICHIER_SQL"
    else
        MYSQL_PWD="$MOTDEPASSE" mysqldump -h "${HOTE:-localhost}" -u "$UTILISATEUR" "$BASE" | gzip > "$FICHIER_SQL"
        info "Sauvegarde : $FICHIER_SQL ($(du -h "$FICHIER_SQL" | cut -f1))"
    fi
    # On garde les 10 dernières.
    ls -1t "$DOSSIER_SAUVEGARDES"/base-*.sql.gz 2>/dev/null | tail -n +11 | xargs -r rm --
else
    erreur "mysqldump absent : sauvegardez la base depuis phpMyAdmin avant de continuer."
    [ "$SIMULATION" = "0" ] && read -r -p "Sauvegarde faite ? [o/N] " reponse && [ "$reponse" = "o" ] || exit 1
fi

# ------------------------------------------------------------------ Maintenance
etape "Mise en maintenance"
executer $PHP artisan down --render="errors::503" --retry=60 || true

# ------------------------------------------------------------------ Code
if [ "$GIT" = "1" ]; then
    etape "Récupération du code"
    executer git fetch --all --prune
    executer git checkout "$BRANCHE"
    executer git pull --ff-only origin "$BRANCHE"
else
    etape "Récupération du code — ignorée (--sans-git)"
fi

etape "Dépendances"
if command -v composer >/dev/null 2>&1; then
    executer composer install --no-dev --optimize-autoloader --no-interaction
else
    info "composer absent : envoyez vendor/ par FTP depuis votre poste."
fi

# ------------------------------------------------------------------ Base
if [ "$MIGRATE" = "1" ]; then
    etape "Migrations"
    executer $PHP artisan migrate --force
else
    etape "Migrations — ignorées (--sans-migrate)"
fi

# ------------------------------------------------------------------ Caches
etape "Caches et liens"
executer $PHP artisan optimize:clear
executer $PHP artisan config:cache
executer $PHP artisan route:cache
executer $PHP artisan view:cache
[ -L public/storage ] || executer $PHP artisan storage:link

etape "Droits"
executer chmod -R 775 storage bootstrap/cache

# ------------------------------------------------------------------ Contrôles
etape "Contrôles après déploiement"
executer $PHP artisan about --only=environment || true
if [ "$SIMULATION" = "0" ]; then
    $PHP artisan aprosafe:comptes || info "Contrôle des comptes patients : écarts signalés, à examiner."
fi

etape "Remise en ligne"
executer $PHP artisan up

printf '\n\033[1;32mDéploiement terminé.\033[0m Vérifiez maintenant : connexion, arrivée d'"'"'un patient, encaissement, impression.\n'
