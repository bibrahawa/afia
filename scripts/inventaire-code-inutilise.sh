#!/usr/bin/env bash
#
# Inventaire du code qui n'est plus atteint : routes vers des méthodes
# absentes, contrôleurs jamais référencés, vues jamais rendues.
#
#   ./scripts/inventaire-code-inutilise.sh
#
# Le script NE SUPPRIME RIEN : il constate et laisse décider. Certains
# fichiers peuvent être appelés par une chaîne construite dynamiquement,
# relisez toujours avant d'effacer.

if [ -z "${BASH_VERSION:-}" ]; then
    exec bash "$0" "$@"
fi

set -Eeuo pipefail

RACINE="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$RACINE"

[ -f artisan ] || { echo "artisan introuvable : lancez le script depuis la racine du projet." >&2; exit 1; }

titre() { printf '\n\033[1;36m%s\033[0m\n' "$1"; }

# ------------------------------------------------------------------ 1. Routes cassées
titre "Routes pointant vers une méthode inexistante"

php artisan route:list --json 2>/dev/null > /tmp/routes-hali.json || {
    printf '  (route:list a échoué — corrigez d abord les erreurs de démarrage)\n'
    exit 1
}

# L'autoloader de Composer est indispensable : sans lui, class_exists()
# répond « non » pour TOUTES les classes de l'application.
php -r '
require __DIR__ . "/vendor/autoload.php";

$routes = json_decode(file_get_contents("/tmp/routes-hali.json"), true) ?: [];
$casse = 0;
foreach ($routes as $route) {
    $action = $route["action"] ?? "";
    if (!str_contains($action, "@")) continue;
    [$classe, $methode] = explode("@", $action, 2);
    if (!class_exists($classe)) { printf("  %-45s classe absente : %s\n", $route["uri"], $classe); $casse++; continue; }
    if (!method_exists($classe, $methode)) { printf("  %-45s méthode absente : %s\n", $route["uri"], $methode); $casse++; }
}
printf($casse ? "\n  %d route(s) cassée(s).\n" : "  Aucune : toutes les routes pointent vers du code existant.\n", $casse);
' || printf '  (contrôle impossible : lancez composer install)\n'

# ------------------------------------------------------------------ 2. Contrôleurs orphelins
titre "Contrôleurs jamais référencés (routes, providers, vues)"

find app/Http/Controllers -name '*Controller.php' | while IFS= read -r fichier; do
    classe="$(basename "$fichier" .php)"
    [ "$classe" = "Controller" ] && continue

    references="$( { grep -rl --exclude-dir=vendor --exclude-dir=node_modules --exclude="$classe.php" \
        -- "$classe" routes app resources config 2>/dev/null || true; } | wc -l | tr -d ' ')"

    [ "$references" = "0" ] && printf '  %s\n' "$fichier"
done

# ------------------------------------------------------------------ 3. Fichiers de routes non chargés
titre "Fichiers de routes non chargés"

for fichier in routes/*.php; do
    nom="$(basename "$fichier")"
    case "$nom" in web.php|api.php|console.php|channels.php) continue ;; esac

    charge="$( { grep -rl -- "$nom" app/Providers bootstrap routes/web.php 2>/dev/null || true; } | wc -l | tr -d ' ')"
    [ "$charge" = "0" ] && printf '  %s  (aucun provider ne le charge)\n' "$fichier"
done

# ------------------------------------------------------------------ 4. Vues jamais rendues
titre "Vues jamais nommées dans le code (échantillon des 20 premières)"

find resources/views -name '*.blade.php' | head -400 | while IFS= read -r vue; do
    nom="$(echo "${vue#resources/views/}" | sed 's/\.blade\.php$//' | tr '/' '.')"
    references="$( { grep -rl --exclude-dir=vendor -- "$nom" app routes resources/views 2>/dev/null || true; } \
        | grep -v "^$vue$" | wc -l | tr -d ' ')"

    [ "$references" = "0" ] && printf '  %s\n' "$vue"
done | head -20

printf '\n\033[1;33mRien n a été supprimé.\033[0m Relisez, puis utilisez git rm pour ce que vous décidez de retirer.\n'
