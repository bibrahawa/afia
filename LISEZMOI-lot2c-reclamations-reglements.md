# Lot 2c — Réclamations, bordereaux, règlements (livraison différentielle)

Prérequis : lot 2b appliqué.

## Déploiement

1. Sauvegarde complète de la base.
2. Copier les fichiers par-dessus le projet.
3. Supprimer les fichiers devenus inutiles (plus aucune route ni référence) :
   app/Http/Controllers/InvoicesController.php
   app/Http/Controllers/InvoiceItemController.php
   app/Http/Controllers/InsuranceClaimController.php
   app/Http/Controllers/InsuranceSettlementController.php
   app/Http/Controllers/InsuranceBalanceController.php
   app/Http/Controllers/Api/Api_InvoiceController.php
   app/Services/InsuranceSettlementService.php
   app/Services/InsuranceCoverageService.php            (si pas déjà fait au 2b)
   resources/views/invoices/index.blade.php
   resources/views/invoices_items/
   resources/views/insurance_claims/
   resources/views/insurance/balance/
4. php artisan migrate
   (détail par acte des réclamations existantes, rattachement des anciens règlements
   aux réclamations des factures mono-assurance)
5. php artisan db:seed --class="Database\Seeders\Assurance\AssurancePermissionsSeeder"
   puis donner : assurance.creances.view, assurance.reclamation.gerer,
   assurance.reglement.enregistrer aux rôles comptabilité / assurance.
6. php artisan optimize:clear
7. php artisan aprosafe:comptes   → écarts dus aux anciens règlements qui n'avaient pas
   débité les comptes patients ; puis php artisan aprosafe:comptes --corriger
8. php artisan test --filter="ReclamationsReglementsTest|MoteurPriseEnChargeTest|FacturationFiableTest"

## Parcours à vérifier

1. Assurance > Créances et règlements : un organisme, ses réclamations ouvertes.
2. Préparer un bordereau du mois, l'imprimer, le marquer envoyé.
3. Ouvrir une réclamation, saisir un montant accepté réduit avec motif.
4. Transférer l'écart au patient OU enregistrer le règlement en imputation manuelle
   avec l'écart en perte.
5. Vérifier la facture (parts) et le compte patient.
