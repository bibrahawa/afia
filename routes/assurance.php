<?php

use App\Http\Controllers\Assurance\AdhesionController;
use App\Http\Controllers\Assurance\ContratController;
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
});
