# Lot 2d — Ajustements assurance (livraison différentielle)

Prérequis : lot 2c appliqué.

## Déploiement

1. Sauvegarde complète de la base.
2. Copier les fichiers par-dessus le projet.
3. Supprimer l'ancien écran des couvertures (plus de route vers lui) :
   resources/views/insurance_coverages/   (index.blade.php, « index.blade copy.php », test.blade.php)
   Le contrôleur InsuranceCoverageController peut rester (store/update/destroy) ou être supprimé
   avec ses 3 routes dans routes/web.php.
4. php artisan migrate
   - organismes de type « entreprise » → « assureur » ; colonne entreprises.organisme_payeur_id supprimée
   - règles de convention par famille, carence et fréquence sur les garanties, pièces justificatives
5. php artisan db:seed --class="Database\Seeders\Assurance\AssurancePermissionsSeeder"
   puis donner « assurance.convention.gerer » au rôle qui négocie les conventions.
6. php artisan optimize:clear
7. php artisan test --filter="ConventionsEtGarantiesTest|ReclamationsReglementsTest|MoteurPriseEnChargeTest|ReferentielAssuranceTest"

## À paramétrer

- Assurance > Conventions : pour chaque organisme, cocher les familles couvertes au prix catalogue
  (pharmacie, laboratoire…), puis ajouter les actes à prix négocié ou exclus.
  « Copier la convention de… » pour démarrer un nouvel assureur.
- Packages : famille « Maternité » pour les forfaits accouchement.
- Contrats > formule : carence maternité (ex. 270 jours), limites (ex. 2 échographies par année de contrat).
