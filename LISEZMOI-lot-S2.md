# Lot S2 — Résultats dans le portail et affiche avec QR code

À appliquer APRÈS le lot S1 (routes/web.php et sidebar en sont les dernières versions).
Aucune migration.

    unzip -o hali-lot-S2.zip -d .
    php artisan optimize:clear

## À tester
1. Portail patient d'un patient qui a un compte rendu publié et réglé :
   Accueil → bandeau « Vos résultats d'analyses sont disponibles » (7 jours) ;
   onglet Dossier → « Résultats d'analyses » → la page de résultats s'ouvre,
   avec « Retour à mon espace santé ».
2. Un compte rendu retenu pour impayé apparaît « Prêt », sans lien.
3. Menu Rendez-vous › Affiche et QR code → scanner le QR avec un téléphone
   AVANT d'imprimer : il doit ouvrir la page de prise de rendez-vous de la clinique.
