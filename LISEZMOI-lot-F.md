# Lot F — Rapports

À appliquer après hali-creances-rapides (routes/web.php issu du lot E2, sidebar issu du lot S2).
Aucune migration.

    unzip -o hali-lot-F.zip -d .
    php artisan optimize:clear

Corrigé :
  - catalogue des rapports : chaque « Ouvrir » menait à /rapports/0, /rapports/1… (404) ;
  - /report et « rapport des actes » : vues supprimées (erreur 500) → redirigés vers Rapports ;
  - /reports/situation-par-acte : accessible sans aucune permission → report.view ;
  - menu : l'entrée « Rapports & Analytics » (page en erreur) retirée.

Vues sans aucune route (à supprimer après vérification) :
    rm resources/views/reports/index.blade.php resources/views/reports/rapport-clinique.blade.php \
       resources/views/reports/tools/rapportCompta.blade.php \
       resources/views/notification/sendSms.blade.php resources/views/notification/messageLists.blade.php

Commit :
    git add -A && git commit -m "fix(rapports): catalogue réparé (liens en 404), anciennes pages redirigées, écrans redessinés"
