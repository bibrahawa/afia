# Lot « analyse du projet » — caisse, « Dr Dr », patients

Aucune migration. Décompresser à la RACINE du projet, puis :

    php artisan route:clear && php artisan view:clear && php artisan optimize:clear
    php artisan test --filter=CaisseTest

## Fichiers à supprimer (plus reliés à aucune route)

    rm resources/views/patients/unpaid.blade.php      # ancien écran, jamais appelé
    rm resources/views/patients/profile.blade.php     # reste du template d'origine
    rm resources/views/invoices/unpaid.blade.php      # AccountController::factureNonPayer, non routé

resources/views/payments/unpaid.blade.php reste en place tant que la caisse n'est pas validée :
le retour arrière est une ligne dans routes/web.php (commentée à côté de la route account.facture).

## Nettoyage facultatif des prénoms « Dr » déjà enregistrés

À vérifier d'abord :

    SELECT id, first_name, last_name FROM employees WHERE first_name REGEXP '^(Dr\\.?|Docteur) ';

Puis, si la liste est correcte (MySQL 8 / MariaDB 10.0.5+) :

    UPDATE employees
       SET first_name = TRIM(REGEXP_REPLACE(first_name, '^((Dr\\.?|Docteur) +)+', ''))
     WHERE first_name REGEXP '^(Dr\\.?|Docteur) ';

L'affichage reste « Dr Alpha Barry » partout (Employee::nom_affiche).
