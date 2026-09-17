# Priorité 3 — Nettoyage de la facturation et des comptes patients

1 migration · 14 fichiers : 3 nouveaux, 10 modifiés, ce LISEZMOI.

## ⚠️ Avant d'installer

1. **Sauvegarde complète de la base.** La migration fusionne les comptes en double : ce n'est pas réversible.
2. `git status` propre.
3. Laravel 10 uniquement : `composer require doctrine/dbal` (nécessaire à `->change()` ; inutile en Laravel 11/12).

```bash
php artisan migrate
php artisan aprosafe:comptes                 # rapport des écarts — ne modifie rien
# vérifier le rapport avec la comptabilité, puis :
php artisan aprosafe:comptes --corriger
php artisan test --filter=ComptesPatients     # 8 tests
php artisan test --filter=Labo                # 60 tests, non-régression
```

## Ce qui était faux

| # | Problème | Conséquence | Correction |
|---|---|---|---|
| 1 | Montants en `FLOAT` (soldes, paiements, factures) | Arrondis qui s'accumulent : 0,1 × 10 ≠ 1 | `DECIMAL(15,2)` |
| 2 | Soldes modifiés en « lire, calculer, écrire » dans 7 endroits | Deux encaissements simultanés : l'un est perdu | Une seule classe écrit le solde (`PatientAccountService`), en `UPDATE balance = balance ± x` |
| 3 | Encaissement débité sur « le » compte du patient, pas celui de la facture | Mauvais compte si doublon | Débit du compte de la transaction |
| 4 | Comptes en double possibles | Soldes éclatés | Fusion + index unique (patient, établissement) |
| 5 | `InvoicesController::consulter` créditait la **part patient**, alors que les encaissements patient **et assurance** débitent le compte | Solde négatif pour les assurés | Crédit du total, comme `BillingService` |
| 6 | Supprimer une consultation / hospitalisation encaissée supprimait ses paiements et retirait le **total** du solde | Argent reçu effacé des rapports, solde faux | **Suppression refusée** s'il y a des paiements ; sinon retrait du reste dû |
| 7 | `ON DELETE CASCADE` compte → factures → paiements | Une suppression effaçait l'historique financier | `RESTRICT` |
| 8 | `Invoice::markAsPaid` posait le statut `completed`, absent de l'énumération | Erreur SQL (mode strict) ou statut vide | Statut recalculé par `TransactionStatusService` ; `completed` existants → `paid` |
| 9 | Pièce sans facture : toujours `pending`, même payée ; ignorée par l'encaissement global | Anciennes consultations impayables, statut faux | Statut depuis total vs payé ; part patient = total |
| 10 | `payPatientTransaction` sur une part déjà réglée | Paiement de 0 GNF enregistré | Refus avec message |
| 11 | `AccountController::payer` avait sa propre logique (statut `paid` en ignorant l'assurance, plantage sans compte, pas de transaction SQL) | Statuts et soldes incohérents selon l'écran utilisé | Délègue à `PaymentService` ; trop-perçu affiché, jamais affecté |
| 12 | `TransactionService` : 3 méthodes jamais appelées, écrivant des colonnes inexistantes | Code mort trompeur | Supprimées ; relais `mettreAJourCompte` conservé (déprécié) |
| 13 | `PackageController::sale/packageSale/packageSales` : table `hospitals` supprimée, modèles inexistants, « Rs. », facture « dernier id + 1 » | Écrans en erreur 500 | Supprimés |

## Changement de comportement à annoncer

- **Une consultation ou hospitalisation déjà encaissée ne peut plus être supprimée.** Message : « Remboursez ou annulez les paiements d'abord. » C'est la règle comptable normale.
- **Encaissement global** : l'excédent est signalé (« trop-perçu à rendre ») au lieu d'être perdu silencieusement.

## À faire de votre côté

Retirez de `routes/web.php` les routes vers `PackageController@sale`, `@packageSale` et `@packageSales`
(`php artisan route:list --path=package` pour les trouver), et le lien « Vente de package » s'il existe dans un menu.

## La commande `aprosafe:comptes`

| Option | Effet |
|---|---|
| *(aucune)* | Tableau des comptes dont le solde ≠ Σ factures − Σ paiements |
| `--etablissement=aprosafe` | Un seul établissement |
| `--seuil=500` | Ignore les écarts < 500 GNF |
| `--corriger` | Aligne les soldes ; chaque correction est journalisée (`comptes.solde_recalcule`, ancien et nouveau solde) |

Convention retenue (celle de `BillingService` et `PaymentService`) : **solde = ce qui reste dû sur les pièces du patient,
parts patient et assurance comprises, dans l'établissement.**

## Fichiers

| Fichier | Statut |
|---|---|
| `database/migrations/2026_09_21_090001_assainir_comptes_patients_et_montants.php` | nouveau |
| `app/Console/Commands/VerifierComptesPatients.php` | nouveau |
| `tests/Feature/Finance/ComptesPatientsTest.php` | nouveau (8 tests) |
| `app/Services/PatientAccountService.php` | réécrit |
| `app/Services/PaymentService.php` | modifié |
| `app/Services/TransactionStatusService.php` | modifié |
| `app/Services/TransactionService.php` | réduit (déprécié) |
| `app/Http/Controllers/AccountController.php` | modifié (`payer`) |
| `app/Http/Controllers/ConsultationController.php` | modifié (`destroy`) — inclut la Priorité 1 |
| `app/Http/Controllers/HospitalisationController.php` | modifié (`destroy`) — inclut la Priorité 1 |
| `app/Http/Controllers/InvoicesController.php` | modifié (`createTransaction`) — inclut la Priorité 1 |
| `app/Http/Controllers/PackageController.php` | code mort supprimé — inclut la Priorité 1 |
| `app/Models/Invoice.php` | modifié (`markAsPaid`) — inclut la facturation multi-établissements |
