<?php

use App\Http\Controllers\Assurance\AdhesionController;
use App\Http\Controllers\Assurance\BordereauController;
use App\Http\Controllers\Assurance\CreanceController;
use App\Http\Controllers\Assurance\ReclamationController;
use App\Http\Controllers\Assurance\ReglementController;
use App\Http\Controllers\Assurance\ContratController;
use App\Http\Controllers\Assurance\ConventionController;
use App\Http\Controllers\Assurance\PieceJustificativeController;
use App\Http\Controllers\Assurance\FeuilleDeSoinsController;
use App\Http\Controllers\Assurance\DroitsController;
use App\Http\Controllers\Assurance\EntrepriseController;
use Illuminate\Support\Facades\Route;

/*
| Préfixe : /assurance — Noms : assurance.* — Middleware : web, auth, module:assurance
| (voir AssuranceServiceProvider). Paramètres nommés assuranceXxx pour éviter
| toute collision avec des paramètres existants.
*/

Route::middleware('can:assurance.referentiel.view')->group(function () {
    // Même recherche que l'accueil (règles de confidentialité identiques), sans exiger la permission de prise de rdv.
    Route::get('patients/recherche', [\App\Http\Controllers\PatientController::class, 'rechercheRapide'])->name('patients.recherche');

    Route::get('entreprises', [EntrepriseController::class, 'index'])->name('entreprises.index');
    Route::get('entreprises/{assuranceEntreprise}', [EntrepriseController::class, 'show'])->name('entreprises.show');
    Route::get('contrats', [ContratController::class, 'index'])->name('contrats.index');
    Route::get('contrats/{assuranceContrat}', [ContratController::class, 'show'])->name('contrats.show');
    Route::get('adhesions/{assuranceAdhesion}', [AdhesionController::class, 'show'])->name('adhesions.show');
    // Vérification des droits à l'accueil
    Route::get('patients/{patientId}/droits', [DroitsController::class, 'show'])->whereNumber('patientId')->name('droits.show');
});

Route::middleware('can:assurance.referentiel.manage')->group(function () {
    // Entreprises et emplois
    Route::post('entreprises', [EntrepriseController::class, 'store'])->name('entreprises.store');
    Route::put('entreprises/{assuranceEntreprise}', [EntrepriseController::class, 'update'])->name('entreprises.update');
    Route::post('entreprises/{assuranceEntreprise}/emplois', [EntrepriseController::class, 'rattacher'])->name('entreprises.emplois.store');
    Route::post('emplois/{assuranceEmploi}/terminer', [EntrepriseController::class, 'terminerEmploi'])->name('emplois.terminer');

    // Contrats et formules
    Route::post('contrats', [ContratController::class, 'store'])->name('contrats.store');
    Route::put('contrats/{assuranceContrat}', [ContratController::class, 'update'])->name('contrats.update');
    Route::post('contrats/{assuranceContrat}/formules', [ContratController::class, 'storeFormule'])->name('formules.store');
    Route::put('formules/{assuranceFormule}', [ContratController::class, 'updateFormule'])->name('formules.update');

    // Adhésions et ayants droit
    Route::post('contrats/{assuranceContrat}/adhesions', [AdhesionController::class, 'store'])->name('adhesions.store');
    Route::post('adhesions/{assuranceAdhesion}/cloturer', [AdhesionController::class, 'cloturer'])->name('adhesions.cloturer');
    Route::post('adhesions/{assuranceAdhesion}/beneficiaires', [AdhesionController::class, 'ajouterBeneficiaire'])->name('beneficiaires.store');
    Route::post('beneficiaires/{assuranceBeneficiaire}/cloturer', [AdhesionController::class, 'cloturerBeneficiaire'])->name('beneficiaires.cloturer');

    // Bons de prise en charge / accords préalables
    Route::post('beneficiaires/{assuranceBeneficiaire}/bons', [DroitsController::class, 'enregistrerBon'])->name('bons.store');
    Route::post('bons/{assurancePriseEnCharge}/annuler', [DroitsController::class, 'annulerBon'])->name('bons.annuler');
});

// ---------------------------------------------------------------- Réclamations, bordereaux, règlements (lot 2c)
Route::middleware('can:assurance.creances.view')->group(function () {
    Route::get('creances', [CreanceController::class, 'index'])->name('creances.index');
    Route::get('creances/{assuranceOrganisme}', [CreanceController::class, 'show'])->name('creances.show');
    Route::get('reclamations/{insuranceClaim}', [ReclamationController::class, 'show'])->name('reclamations.show');
    Route::get('bordereaux/{assuranceBordereau}', [BordereauController::class, 'show'])->name('bordereaux.show');
    Route::get('bordereaux/{assuranceBordereau}/imprimer', [BordereauController::class, 'imprimer'])->name('bordereaux.imprimer');
    Route::get('reglements/{insuranceSettlement}', [ReglementController::class, 'show'])->name('reglements.show');
});

Route::middleware('can:assurance.reclamation.gerer')->group(function () {
    Route::post('creances/{assuranceOrganisme}/bordereaux', [BordereauController::class, 'store'])->name('bordereaux.store');
    Route::post('bordereaux/{assuranceBordereau}/envoyer', [BordereauController::class, 'envoyer'])->name('bordereaux.envoyer');
    Route::post('bordereaux/{assuranceBordereau}/rouvrir', [BordereauController::class, 'rouvrir'])->name('bordereaux.rouvrir');
    Route::delete('bordereaux/{assuranceBordereau}/reclamations/{insuranceClaim}', [BordereauController::class, 'retirer'])->name('bordereaux.retirer');
    Route::post('reclamations/{insuranceClaim}/reponse', [ReclamationController::class, 'repondre'])->name('reclamations.repondre');
    Route::post('reclamations/{insuranceClaim}/transferer-au-patient', [ReclamationController::class, 'transferer'])->name('reclamations.transferer');
});

Route::middleware('can:assurance.reglement.enregistrer')->group(function () {
    Route::post('creances/{assuranceOrganisme}/reglements', [ReglementController::class, 'store'])->name('reglements.store');
});

// ---------------------------------------------------------------- Conventions, feuille de soins, pièces (lot 2d)
Route::middleware('can:assurance.referentiel.view')->group(function () {
    Route::get('conventions', [ConventionController::class, 'index'])->name('conventions.index');
    Route::get('conventions/{assuranceOrganisme}', [ConventionController::class, 'show'])->name('conventions.show');
    Route::get('feuilles-de-soins/{transactionId}', [FeuilleDeSoinsController::class, 'show'])->whereNumber('transactionId')->name('feuilles-de-soins.show');
});

Route::middleware('can:assurance.convention.gerer')->group(function () {
    Route::post('conventions/{assuranceOrganisme}/familles', [ConventionController::class, 'enregistrerFamilles'])->name('conventions.familles');
    Route::post('conventions/{assuranceOrganisme}/actes', [ConventionController::class, 'ajouterActe'])->name('conventions.actes.store');
    Route::put('conventions/actes/{insuranceCoverage}', [ConventionController::class, 'modifierActe'])->name('conventions.actes.update');
    Route::delete('conventions/actes/{insuranceCoverage}', [ConventionController::class, 'supprimerActe'])->name('conventions.actes.destroy');
    Route::post('conventions/{assuranceOrganisme}/copier', [ConventionController::class, 'copier'])->name('conventions.copier');
});

Route::middleware('can:assurance.creances.view')->group(function () {
    Route::get('pieces/{assurancePiece}', [PieceJustificativeController::class, 'telecharger'])->name('pieces.telecharger');
});

Route::middleware('can:assurance.reclamation.gerer')->group(function () {
    Route::post('reclamations/{insuranceClaim}/pieces', [PieceJustificativeController::class, 'store'])->name('pieces.store');
    Route::delete('pieces/{assurancePiece}', [PieceJustificativeController::class, 'destroy'])->name('pieces.destroy');
});
