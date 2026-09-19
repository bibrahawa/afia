# Lot S4 — La cloche (alertes utiles uniquement)

À appliquer APRÈS le lot S3 (routes/web.php) et le lot C2 (navbar.blade.php).
Aucune migration.

    unzip -o hali-lot-S4.zip -d .
    php artisan optimize:clear

Trois alertes, calculées à partir de l'état réel (aucune table, rien à « marquer comme lu ») :
  1. Valeur critique non signalée (labo)       → biologistes / techniciens de validation
  2. Patient en attente depuis plus d'1 h       → accueil (toute la file), médecin (sa file)
  3. Réclamation rejetée, écart non réglé       → gestion des réclamations d'assurance
Chaque alerte disparaît d'elle-même quand le problème est réglé (délai max. 1 min : cache).

## À tester
1. Faire arriver un patient à l'accueil, modifier son heure d'arrivée à -65 min en base
   (UPDATE visites SET arrivee_le = NOW() - INTERVAL 65 MINUTE WHERE id = …) → la cloche l'affiche ;
   l'appeler en consultation → l'alerte disparaît dans la minute.
2. Saisir une valeur critique au labo sans la signaler → alerte rouge ; la signaler → disparaît.
