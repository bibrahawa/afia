# Lot E1 — Personnel et accès fusionnés, suspension réelle

À appliquer APRÈS le lot D (routes/web.php issu de S4, SmsService issu de S1,
EmployeeController et vues du personnel issus de C2).

    unzip -o hali-lot-E1.zip -d .
    php artisan migrate      # users.doit_changer_mot_de_passe ; users.email devient facultatif
    php artisan optimize:clear

bootstrap/app.php est modifié (une ligne : middleware VerifierCompteActif ajouté au groupe web).

## Vues devenues inutiles (aucune route n'y mène) — à supprimer après vérification
    rm resources/views/users/{create,edit,show,profil-user,show_permissions,update_permissions}.blade.php

## À tester
1. Personnel › Nouvel employé › cocher « Lui créer un accès » › rôle Secrétariat › Enregistrer :
   SMS reçu ; première connexion → obligée de choisir son mot de passe ; ensuite tout fonctionne.
2. Couper le réseau / mauvais numéro : le mot de passe provisoire s'affiche une fois sur la fiche.
3. Suspendre un compte connecté dans un autre navigateur : il est déconnecté au clic suivant,
   et ne peut plus se reconnecter (message « Ce compte est suspendu »).
4. Journal des SMS : le mot de passe provisoire apparaît masqué (••••••).

Commit :
    git add -A && git commit -m "feat(personnel): fiche et accès fusionnés, mot de passe provisoire par SMS, suspension réellement appliquée"
