# Lot 4b — Facturation entre établissements (livraison différentielle)

Prérequis : lot 4a appliqué.

## Déploiement

1. Sauvegarde de la base.
2. Décompresser à la RACINE du projet.
3. php artisan migrate
   (labo_releves_partenaires, labo_creances_partenaires, labo_reglements_partenaires,
    labo_reglement_imputations)
4. php artisan db:seed --class="Database\Seeders\Labo\LaboReseauPermissionsSeeder"
   Nouvelles permissions : labo.partenariat.facturer (laboratoire, comptable),
   labo.reseau.factures (clinique).
5. php artisan optimize:clear
6. php artisan test --filter="FacturationPartenaireTest|ReseauLaboTest"

## Parcours à vérifier

1. Partenariat réglé sur « Le laboratoire facture la clinique ».
2. La clinique envoie deux demandes : le patient n'est pas encaissé au guichet du labo.
3. Laboratoire > Créances partenaires : les analyses apparaissent « à facturer ».
4. Préparer un relevé sur le mois écoulé, l'imprimer, le marquer envoyé (montants figés).
5. Enregistrer un règlement partiel puis le solde : le relevé passe à « soldé ».
6. Côté clinique : menu « Factures laboratoires », le relevé et son détail sont visibles.
7. Essayer d'annuler une demande déjà sur un relevé envoyé : refusé, avec le numéro du relevé.
