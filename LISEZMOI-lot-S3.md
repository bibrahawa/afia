# Lot S3 — Catalogue : masquer au lieu de supprimer, import Excel

À appliquer APRÈS le lot S2 (routes/web.php en est la dernière version) et après
les lots C1 (écrans du catalogue) et consultation-directe (contrôleurs de consultation).

    unzip -o hali-lot-S3.zip -d .
    php artisan migrate        # colonne « actif » sur services et medicaments (tout reste visible)
    php artisan optimize:clear

Extensions PHP nécessaires pour lire les .xlsx : zip et SimpleXML (présentes sur les
hébergements usuels ; sinon l'écran demande un fichier CSV).

## À tester
1. Actes : cliquer l'œil sur un acte → il disparaît de la liste (case « Afficher les masqués »),
   n'est plus proposé à l'accueil ni en consultation, mais les anciennes factures l'affichent toujours.
2. Catalogue › Importer : télécharger le modèle « actes », ajouter 3 lignes, renvoyer le fichier
   → aperçu → « Importer ». Renvoyer le même fichier : tout doit être « Ignoré ».
3. Un fichier Excel à vous, avec vos propres titres de colonnes (« Désignation », « Tarif »…).
