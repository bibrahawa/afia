# Lot 3e — Certificats, croissance, nouveau-né, rappels CPN, salle d'attente

Prérequis : lot 3d appliqué.

## Déploiement

1. Sauvegarde de la base.
2. Décompresser à la RACINE du projet (dossiers en français).
3. php artisan migrate    (documents_medicaux, normes_croissance, grossesse_rappels)
4. php artisan db:seed --class="Database\Seeders\Parcours\ParcoursPermissionsSeeder"
   (nouvelle permission : parcours.document)
5. php artisan optimize:clear
6. php artisan test --filter="DocumentsCroissanceEtRappelsTest|OrdreFileEtCorrectionsTest|DossierEtGrossesseTest|ConsultationRapideTest|AccueilEtFileAttenteTest"

## Courbes de croissance : import des tables OMS

Les tables de référence ne sont pas fournies (ce sont les fichiers officiels de l'OMS,
« Child Growth Standards », tables L/M/S par mois). Les télécharger, puis :

    php artisan aprosafe:importer-normes-oms wfa-boys.txt   poids_age  Homme
    php artisan aprosafe:importer-normes-oms wfa-girls.txt  poids_age  Femme
    php artisan aprosafe:importer-normes-oms lhfa-boys.txt  taille_age Homme
    php artisan aprosafe:importer-normes-oms lhfa-girls.txt taille_age Femme
    php artisan aprosafe:importer-normes-oms bfa-boys.txt   imc_age    Homme
    php artisan aprosafe:importer-normes-oms bfa-girls.txt  imc_age    Femme

Sans import, l'écran affiche les mesures de l'enfant sans z-score : aucune valeur n'est inventée.

## Rappels de consultations prénatales

À planifier une fois par jour (app/Console/Kernel.php ou routes/console.php) :

    php artisan aprosafe:rappels-cpn --jours=3
    php artisan aprosafe:rappels-cpn --jours=3 --test   (affiche sans envoyer)

## Écran salle d'attente

Ouvrir /parcours/salle-attente en plein écran sur le téléviseur (le menu propose le lien).
La page se rafraîchit toute seule toutes les 20 secondes ; seuls le prénom et l'initiale
du nom sont affichés.
