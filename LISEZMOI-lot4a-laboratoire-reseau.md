# Lot 4a — Laboratoire en réseau (livraison différentielle)

Prérequis : module Laboratoire déjà installé côté laboratoire, lots 3a-3e appliqués.

## Déploiement

1. Sauvegarde de la base.
2. Décompresser à la RACINE du projet (dossiers en français : app/Services/Labo, resources/views/labo…).
3. php artisan migrate     (labo_partenariats, labo_demandes.partenariat_id)
4. php artisan db:seed --class="Database\Seeders\Labo\LaboReseauPermissionsSeeder"
   Nouvelles permissions : labo.partenariat.gerer (laboratoire),
   labo.reseau.view et labo.reseau.demander (clinique prescriptrice).
5. php artisan optimize:clear
6. php artisan test --filter=ReseauLaboTest

## Important

La clinique prescriptrice n'a PAS besoin du module « laboratoire » activé :
les routes du réseau (/laboratoire-reseau) ne passent pas par EnsureModuleActive.
C'est le partenariat actif qui fait l'autorisation.

## Parcours à vérifier

1. Côté laboratoire : menu Laboratoire > Cliniques partenaires > Nouveau partenariat.
   Choisir la clinique, le mode de facturation et la remise.
2. Côté clinique : menu « Analyses envoyées » > Envoyer une demande.
   Le catalogue du laboratoire s'affiche aux prix négociés.
3. Depuis une consultation : bouton « Analyses — laboratoire partenaire ».
4. Côté laboratoire : la demande apparaît dans les demandes du jour, marquée « externe »,
   avec la clinique comme prescripteur. Le circuit habituel (prélèvement, paillasse,
   validation, publication) ne change pas.
5. Côté clinique : le compte rendu publié devient consultable et imprimable.
6. Suspendre le partenariat : la clinique ne peut plus envoyer de nouvelle demande,
   les demandes en cours se terminent normalement.
