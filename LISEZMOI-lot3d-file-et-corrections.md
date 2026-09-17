# Lot 3d — Ordre de la file et corrections du parcours (livraison différentielle)

Prérequis : lot 3c appliqué (avec les deux correctifs : AccueilService, Grossesse).

## Déploiement

1. Sauvegarde de la base.
2. Décompresser à la RACINE du projet (dossiers en français : app/Services/Parcours, resources/views/parcours…).
3. php artisan migrate      (etablissements.ordre_file, visites.rang)
4. php artisan db:seed --class="Database\Seeders\Parcours\ParcoursPermissionsSeeder"
   (la secrétaire perd « parcours.grossesse » : si vous voulez la lui laisser, ajoutez-la à la main)
5. php artisan optimize:clear
6. php artisan test --filter="OrdreFileEtCorrectionsTest|DossierEtGrossesseTest|ConsultationRapideTest|AccueilEtFileAttenteTest"

## À vérifier

1. Accueil : choisir la règle (« ordre d'arrivée » ou « rendez-vous d'abord »), puis faire passer un patient
   en tête, le monter, le descendre, revenir à la règle de la clinique.
2. Patient reparti : la facture de l'acte est annulée si rien n'a été encaissé.
3. Écran médecin : saisir une ordonnance, couper le réseau, recharger la page — la saisie est restaurée.
4. Retirer un acte déjà payé : refusé avec un message clair, sans perdre la saisie.
5. Écran médecin : allergies et antécédents modifiables, constantes, hospitalisation, demande d'analyses.
6. Prochain rendez-vous : posé sur le premier créneau réellement libre.
