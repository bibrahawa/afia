#!/usr/bin/env bash
#
# Retire les tests livrés avec le squelette Laravel/Breeze qui ne correspondent
# à rien dans Hali : il n'y a ni inscription publique, ni page d'accueil à la
# racine, ni réinitialisation de mot de passe par e-mail.
#
#   ./scripts/nettoyer-tests-squelette.sh              inventaire
#   ./scripts/nettoyer-tests-squelette.sh --executer   suppression
#
# Les tests d'authentification, de profil et de mot de passe CONNECTÉ sont
# conservés : ils couvrent des écrans réellement utilisés.

if [ -z "${BASH_VERSION:-}" ]; then
    exec bash "$0" "$@"
fi

set -Eeuo pipefail

RACINE="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$RACINE"

EXECUTER=0
[ "${1:-}" = "--executer" ] && EXECUTER=1

SANS_OBJET=(
    tests/Feature/Auth/RegistrationTest.php
    tests/Feature/Auth/PasswordResetTest.php
    tests/Feature/ExampleTest.php
    tests/Unit/ExampleTest.php
    tests/Feature/SendAppointmentRemindersTest.php
)

printf '\033[1;36mTests du squelette sans objet dans Hali\033[0m\n\n'

TOTAL=0
for fichier in "${SANS_OBJET[@]}"; do
    [ -f "$fichier" ] || continue
    TOTAL=$((TOTAL + 1))

    if [ "$EXECUTER" = "1" ]; then
        rm -f -- "$fichier"
        printf '  supprimé    %s\n' "$fichier"
    else
        printf '  à supprimer %s\n' "$fichier"
    fi
done

printf '\n'
if [ "$TOTAL" = "0" ]; then
    printf 'Rien à faire : ils ont déjà été retirés.\n'
elif [ "$EXECUTER" = "1" ]; then
    printf '\033[1;32m%s fichier(s) supprimé(s).\033[0m Relancez : php artisan test\n' "$TOTAL"
else
    printf '\033[1;33m%s fichier(s) concerné(s).\033[0m Relancez avec --executer.\n' "$TOTAL"
fi

printf '\nConservés volontairement : AuthenticationTest, EmailVerificationTest,\n'
printf 'PasswordConfirmationTest, PasswordUpdateTest, ProfileTest — ils testent\n'
printf 'des écrans réels, et passent une fois UserFactory corrigée.\n'
