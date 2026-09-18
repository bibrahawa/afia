#!/usr/bin/env bash
#
# Inventaire (et, si demandé, remplacement) des occurrences de l'ancienne marque.
#
#   ./scripts/renommer-marque.sh                   inventaire seul
#   ./scripts/renommer-marque.sh --remplacer       remplace dans les fichiers listés
#
# ATTENTION : « Aprosafe » désigne AUSSI la clinique pilote (nom d'établissement
# en base, données de démonstration). Le script ne touche donc jamais :
#   - database/migrations et database/seeders (données de l'établissement pilote)
#   - les numéros déjà émis (FAC-, LAB-, CRT-, ARR-, REL-, BRD-, CLM-)
#   - les noms de tables et de colonnes
# Relisez toujours l'inventaire avant de lancer --remplacer.

if [ -z "${BASH_VERSION:-}" ]; then
    exec bash "$0" "$@"
fi

set -Eeuo pipefail

RACINE="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$RACINE"

ANCIEN="${ANCIEN:-Aprosafe}"
NOUVEAU="${NOUVEAU:-Hali}"
REMPLACER=0

for argument in "$@"; do
    case "$argument" in
        --remplacer) REMPLACER=1 ;;
        *) echo "Option inconnue : $argument" >&2; exit 1 ;;
    esac
done

[ -f artisan ] || { echo "artisan introuvable : lancez le script depuis la racine du projet." >&2; exit 1; }

printf '\033[1;36mOccurrences de « %s » (hors vendor, node_modules, storage, migrations, seeders)\033[0m\n\n' "$ANCIEN"

FICHIERS="$( { grep -rl --exclude-dir=vendor --exclude-dir=node_modules --exclude-dir=storage \
    --exclude-dir=.git --exclude-dir=migrations --exclude-dir=seeders \
    --include='*.php' --include='*.blade.php' --include='*.md' --include='*.js' --include='*.json' \
    -- "$ANCIEN" . 2>/dev/null || true; } | sort )"

if [ -z "$FICHIERS" ]; then
    printf 'Aucune occurrence : le renommage est terminé.\n'
    exit 0
fi

TOTAL=0
while IFS= read -r fichier; do
    [ -n "$fichier" ] || continue
    nombre="$(grep -c -- "$ANCIEN" "$fichier" || true)"
    TOTAL=$((TOTAL + nombre))
    printf '  %-70s %3s occurrence(s)\n' "$fichier" "$nombre"

    if [ "$REMPLACER" = "1" ]; then
        # Sauvegarde à côté du fichier, à supprimer une fois la relecture faite.
        cp "$fichier" "$fichier.avant-renommage"
        sed -i.tmp "s/$ANCIEN/$NOUVEAU/g; s/$(echo "$ANCIEN" | tr '[:lower:]' '[:upper:]')/$(echo "$NOUVEAU" | tr '[:lower:]' '[:upper:]')/g" "$fichier"
        rm -f "$fichier.tmp"
    fi
done <<< "$FICHIERS"

printf '\n'
if [ "$REMPLACER" = "1" ]; then
    printf '\033[1;32m%s occurrence(s) remplacées par « %s ».\033[0m\n' "$TOTAL" "$NOUVEAU"
    printf 'Sauvegardes : *.avant-renommage — relisez, testez, puis supprimez-les :\n'
    printf '  find . -name "*.avant-renommage" -delete\n'
else
    printf '\033[1;33m%s occurrence(s) trouvées.\033[0m Relancez avec --remplacer après relecture.\n' "$TOTAL"
fi

printf '\nÀ faire à la main, hors de ce script :\n'
printf '  - APP_NAME et MARQUE_* dans .env, sur chaque serveur\n'
printf '  - expéditeur SMS chez l opérateur (11 caracteres maximum)\n'
printf '  - logo et favicon dans public/\n'
printf '  - nom de l etablissement pilote en base, s il doit changer\n'
