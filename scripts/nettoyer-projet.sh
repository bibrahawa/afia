#!/usr/bin/env bash
#
# Nettoyage du projet Aprosafe : caches, artefacts de développement et code mort
# identifié lors des refontes (assurance, facturation, laboratoire).
#
#   ./scripts/nettoyer-projet.sh                 SIMULATION : rien n'est supprimé
#   ./scripts/nettoyer-projet.sh --executer      supprime réellement
#   ./scripts/nettoyer-projet.sh --executer --avec-code-mort
#   ./scripts/nettoyer-projet.sh --executer --avec-vendor
#
# Par défaut le script NE SUPPRIME RIEN : il affiche ce qu'il ferait.
# Le code mort n'est retiré qu'avec --avec-code-mort, après contrôle des références.

set -Eeuo pipefail

RACINE="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$RACINE"

EXECUTER=0; CODE_MORT=0; VENDOR=0

for argument in "$@"; do
    case "$argument" in
        --executer) EXECUTER=1 ;;
        --avec-code-mort) CODE_MORT=1 ;;
        --avec-vendor) VENDOR=1 ;;
        *) echo "Option inconnue : $argument" >&2; exit 1 ;;
    esac
done

[ -f artisan ] || { echo "artisan introuvable : lancez le script depuis la racine du projet." >&2; exit 1; }

TOTAL=0

supprimer() {
    local cible="$1" raison="$2"

    [ -e "$cible" ] || return 0

    local taille
    taille="$(du -sh "$cible" 2>/dev/null | cut -f1)"
    TOTAL=$((TOTAL + 1))

    if [ "$EXECUTER" = "1" ]; then
        rm -rf -- "$cible"
        printf '  supprimé   %-58s %6s  %s\n' "$cible" "$taille" "$raison"
    else
        printf '  à supprimer %-57s %6s  %s\n' "$cible" "$taille" "$raison"
    fi
}

titre() { printf '\n\033[1;36m%s\033[0m\n' "$1"; }

if [ "$EXECUTER" = "0" ]; then
    printf '\033[1;33mSIMULATION — rien ne sera supprimé. Ajoutez --executer pour agir.\033[0m\n'
fi

# ------------------------------------------------------------------ Caches
titre "Caches applicatifs (régénérés automatiquement)"
for fichier in bootstrap/cache/*.php; do supprimer "$fichier" "cache de configuration"; done
for fichier in storage/framework/views/*.php; do supprimer "$fichier" "vue compilée"; done
supprimer storage/framework/cache/data "cache applicatif"
supprimer storage/debugbar "traces de débogage"

titre "Journaux de plus de 30 jours"
if [ -d storage/logs ]; then
    while IFS= read -r ancien; do supprimer "$ancien" "journal ancien"; done \
        < <(find storage/logs -name '*.log' -type f -mtime +30 2>/dev/null)
fi

# ------------------------------------------------------------------ Artefacts
titre "Artefacts de développement"
supprimer .phpunit.result.cache "cache PHPUnit"
supprimer .phpunit.cache "cache PHPUnit"
supprimer npm-debug.log "journal npm"
supprimer yarn-error.log "journal yarn"

titre "Fichiers laissés par macOS, Windows et les éditeurs"
while IFS= read -r parasite; do supprimer "$parasite" "fichier système"; done < <(
    find . -path ./vendor -prune -o -path ./node_modules -prune -o \
        \( -name '.DS_Store' -o -name 'Thumbs.db' -o -name '*.swp' -o -name '*~' \
           -o -name '*.orig' -o -name '*.rej' -o -name '*.bak' \) -type f -print 2>/dev/null
)

titre "Sauvegardes et archives oubliées à la racine"
while IFS= read -r archive; do supprimer "$archive" "archive de livraison"; done < <(
    find . -maxdepth 1 \( -name '*.zip' -o -name '*.tar.gz' -o -name '*.sql' -o -name '*.sql.gz' \) -type f 2>/dev/null
)

# ------------------------------------------------------------------ Code mort
if [ "$CODE_MORT" = "1" ]; then
    titre "Code mort (remplacé par les modules Facturation et Assurance)"

    # Ces fichiers n'ont plus ni route ni référence depuis les lots 2c et 2d.
    MORTS=(
        app/Http/Controllers/InvoicesController.php
        app/Http/Controllers/InvoiceItemController.php
        app/Http/Controllers/InsuranceClaimController.php
        app/Http/Controllers/InsuranceSettlementController.php
        app/Http/Controllers/InsuranceBalanceController.php
        app/Http/Controllers/Api/Api_InvoiceController.php
        app/Services/InsuranceSettlementService.php
        app/Services/InsuranceCoverageService.php
        resources/views/invoices/index.blade.php
        resources/views/invoices_items
        resources/views/insurance_claims
        resources/views/insurance/balance
        resources/views/insurance_coverages
    )

    for mort in "${MORTS[@]}"; do
        [ -e "$mort" ] || continue

        base="$(basename "$mort" .php)"
        # `|| true` : sans lui, grep sans résultat (ou dossier absent) ferait
        # échouer l'affectation, et `set -e` couperait le script en silence.
        references="$( { grep -rl --exclude-dir=vendor --exclude-dir=node_modules --exclude-dir=storage \
            --exclude="$(basename "$mort")" -- "$base" app routes resources config 2>/dev/null || true; } | wc -l | tr -d ' ')"

        if [ "$references" != "0" ]; then
            printf '  \033[1;33mconservé\033[0m   %-57s        %s référence(s) encore trouvée(s) — à vérifier\n' "$mort" "$references"
            continue
        fi

        supprimer "$mort" "code mort"
    done
else
    titre "Code mort — ignoré (ajoutez --avec-code-mort)"
fi

# ------------------------------------------------------------------ Dépendances
if [ "$VENDOR" = "1" ]; then
    titre "Dépendances (à réinstaller ensuite)"
    supprimer node_modules "réinstallable par npm install"
    supprimer vendor "réinstallable par composer install"
fi

# ------------------------------------------------------------------ Bilan
printf '\n'
if [ "$EXECUTER" = "1" ]; then
    printf '\033[1;32m%s élément(s) supprimé(s).\033[0m\n' "$TOTAL"
    printf 'Reconstruisez les caches : php artisan optimize\n'
    [ "$VENDOR" = "1" ] && printf 'Réinstallez les dépendances : composer install && npm install\n'
else
    printf '\033[1;33m%s élément(s) seraient supprimés.\033[0m Relancez avec --executer.\n' "$TOTAL"
fi

printf '\nJamais touchés par ce script : .env, storage/app (pièces justificatives, PDF, sauvegardes),\n'
printf 'database/migrations, et tout fichier encore référencé dans le code.\n'
