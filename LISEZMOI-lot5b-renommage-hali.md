# Lot 5b — La plateforme s'appelle Hali

Prérequis : lot 5a appliqué. Aucune migration.

## Déploiement

1. Décompresser à la RACINE du projet.
2. Ajouter dans .env, sur CHAQUE environnement :

       APP_NAME=Hali
       MARQUE_NOM=Hali
       MARQUE_SIGNATURE="Hali — plateforme de gestion clinique"
       MARQUE_SMS_EXPEDITEUR=HALI
       MARQUE_SITE=https://hali.gn
       MARQUE_SUPPORT=support@hali.gn

3. php artisan optimize:clear
4. php artisan test
5. Passer en revue le reste : bash scripts/renommer-marque.sh

## Ce qui change

- config/marque.php + App\Support\Marque : un seul endroit pour le nom commercial,
  lu par les écrans, les documents, les e-mails et les SMS.
- Écran de connexion, titres de pages, SMS de résultats : marque dynamique.
- Commandes renommées en hali:*, ANCIEN NOM CONSERVÉ EN ALIAS :
  hali:comptes, hali:compte, hali:rappels-cpn, hali:importer-normes-oms, hali:demo.
  Les crons en place continuent donc de fonctionner ; mettez-les à jour quand vous voulez.
- README et guide de déploiement réécrits (chemins /var/www/hali, domaine app.hali.gn).
- scripts/renommer-marque.sh : inventaire des occurrences restantes, et remplacement
  sur demande (--remplacer), avec sauvegarde de chaque fichier touché.

## Ce qui NE change pas, volontairement

- Les noms de tables et de colonnes.
- Les préfixes des numéros déjà émis : FAC-, LAB-, CRT-, ARR-, REL-, BRD-, CLM-.
- Le nom « Aprosafe » là où il désigne la CLINIQUE PILOTE (établissement en base,
  migrations et seeders) : c'est un client, pas la plateforme.

## À faire hors du code

- Expéditeur SMS chez l'opérateur : 11 caractères maximum, validation à demander tôt.
- Logo et favicon dans public/, en-têtes des documents imprimés.
- Domaine, adresses e-mail, dépôt de marque à l'OAPI.
