# Créances assurance instantanées

À appliquer après le lot E2. Contient une migration (qui remplit les nouvelles colonnes).

    unzip -o hali-creances-rapides.zip -d .
    php artisan migrate                      # ajoute les colonnes et lance le premier calcul
    php artisan schedule:list                # 9 tâches (nouvelle : assurance:recalculer-creances à 3 h)

Contrôle après migration (doit afficher « 0 mise(s) à jour ») :
    php artisan assurance:recalculer-creances

Commit :
    git add -A && git commit -m "perf(assurance): reste dû stocké sur les réclamations, écran des créances en une requête"
