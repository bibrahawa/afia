# Lot R2 — API fermée, sauvegardes, mot de passe oublié, nettoyage

À appliquer après le lot R. Contient une migration (retrait d'un index en double).

    unzip -o hali-lot-R2.zip -d .
    php artisan migrate
    php artisan optimize:clear
    php artisan schedule:list        # 10 tâches (nouvelle : hali:sauvegarder à 2 h 30)

## 1. API fermée
routes/api.php ne contient plus aucune route (liste de ce qui a été retiré en commentaire).

## 2. Sauvegarde quotidienne (2 h 30)
Dans le .env du serveur :

    # Chiffrement (FORTEMENT recommandé : données de santé). Au moins 12 caractères.
    # À CONSERVER HORS DU SERVEUR (gestionnaire de mots de passe) : sans elle, aucune
    # sauvegarde chiffrée ne peut être restaurée.
    HALI_SAUVEGARDE_CLE="une phrase longue que vous seul connaissez"

    HALI_SAUVEGARDE_ALERTE_TEL=622000000     # SMS si une sauvegarde échoue
    HALI_SAUVEGARDE_JOURS_LOCAL=7
    HALI_SAUVEGARDE_JOURS_DISTANT=30

    # Copie hors du serveur — SFTP conseillé (un autre serveur, un NAS, un VPS) :
    HALI_SAUVEGARDE_DRIVER=sftp
    HALI_SAUVEGARDE_HOTE=sauvegarde.exemple.gn
    HALI_SAUVEGARDE_PORT=22
    HALI_SAUVEGARDE_UTILISATEUR=hali
    HALI_SAUVEGARDE_MOT_DE_PASSE=...
    HALI_SAUVEGARDE_DOSSIER=/sauvegardes-hali

Paquet à installer selon la copie choisie (une seule fois) :
    sftp : composer require league/flysystem-sftp-v3 "^3.0"
    ftp  : composer require league/flysystem-ftp "^3.0"        (+ HALI_SAUVEGARDE_FTP_SSL=true)
    s3   : composer require league/flysystem-aws-s3-v3 "^3.0"  (HALI_SAUVEGARDE_S3_CLE, _SECRET, _REGION, _BUCKET, _ENDPOINT)

Premier essai manuel :
    php artisan hali:sauvegarder

À FAIRE UNE FOIS : tester une restauration sur une base de test (une sauvegarde jamais
restaurée n'est pas une sauvegarde) :
    php artisan hali:dechiffrer-sauvegarde storage/app/sauvegardes/hali-AAAAMMJJ-HHMMSS.sql.gz.chiffre
    gunzip < hali-AAAAMMJJ-HHMMSS.sql.gz | mysql -u UTILISATEUR -p BASE_DE_TEST

## 3. Mot de passe oublié (personnel)
Le lien « Mot de passe oublié ? » apparaît sur la page de connexion. Code SMS de 6 chiffres,
10 minutes, 5 essais ; ne révèle pas si un numéro a un compte ; compte suspendu exclu.
Règle de mot de passe partout : 8 caractères, au moins une lettre et un chiffre.

## 4. Performance
Index en double retiré (insurance_claims) ; écran détaillé d'un assureur : seules les
réclamations qui doivent encore quelque chose sont chargées.

## 5. Nettoyage (après avoir vérifié que tout fonctionne)
    bash nettoyage-hali.sh
42 fichiers morts (vues, SmsController, SmsReportController, RouteServiceProvider), supprimés
par « git rm ». Le script s'arrête sans rien supprimer si l'un d'eux est de nouveau utilisé.

Commit :
    git add -A && git commit -m "feat(securite): API fermée, sauvegarde quotidienne chiffrée, mot de passe oublié par SMS"
