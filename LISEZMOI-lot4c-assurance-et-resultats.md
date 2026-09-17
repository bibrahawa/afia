# Lot 4c — Assurance sur les analyses envoyées et retour des résultats

Prérequis : lots 4a et 4b appliqués.

## Déploiement

1. Sauvegarde de la base.
2. Décompresser à la RACINE du projet.
3. php artisan migrate
   (labo_partenariats.clinique_facture_patient, labo_partenariat_actes,
    labo_demandes.resultat_notifie_le / resultat_vu_le / resultat_vu_par)
4. php artisan optimize:clear
5. php artisan test --filter="AssuranceEtNotificationReseauTest|FacturationPartenaireTest|ReseauLaboTest"

Aucune nouvelle permission.

## Parcours à vérifier

1. Laboratoire > Cliniques partenaires : cocher « La clinique facture son patient ».
   Renseigner le téléphone du contact pour recevoir les notifications.
2. Clinique : envoyer une demande. Une facture est créée DANS la clinique, sur son
   catalogue d'examens, avec ses conventions d'assurance. Le laboratoire garde sa créance.
3. Vérifier au catalogue de la clinique les actes créés automatiquement (prix négocié) :
   les retarifer si la clinique prend une marge.
4. Laboratoire : publier le compte rendu. Un SMS part vers le contact de la clinique.
5. Clinique : « Analyses envoyées » signale les nouveaux résultats ; le signalement
   disparaît à l'ouverture de la demande.
6. Annuler une demande facturée par la clinique : sa facture est annulée si rien
   n'a été encaissé, refusé sinon.
