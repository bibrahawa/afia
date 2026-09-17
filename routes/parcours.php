<?php

use App\Http\Controllers\Parcours\AccueilController;
use App\Http\Controllers\Parcours\ConsultationRapideController;
use App\Http\Controllers\Parcours\DossierController;
use App\Http\Controllers\Parcours\GrossesseController;
use App\Http\Controllers\Parcours\StatistiqueController;
use App\Http\Controllers\Parcours\FileAttenteController;
use App\Http\Controllers\PatientController;
use Illuminate\Support\Facades\Route;

/*
| Préfixe : /parcours — Noms : parcours.* — Middleware : web, auth, module:consultation
| (voir ParcoursServiceProvider).
*/

Route::middleware('can:parcours.accueil')->group(function () {
    Route::get('accueil', [AccueilController::class, 'index'])->name('accueil.index');
    Route::get('patients/recherche', [PatientController::class, 'rechercheRapide'])->name('patients.recherche');
    Route::post('accueil/arrivee', [AccueilController::class, 'arriveeSansRendezVous'])->name('accueil.arrivee');
    Route::post('accueil/rendez-vous/{appointment}/arrivee', [AccueilController::class, 'arriveeRendezVous'])->name('accueil.arrivee-rdv');
    Route::post('accueil/rendez-vous/{appointment}/absent', [AccueilController::class, 'absent'])->name('accueil.absent');
    Route::post('accueil/visites/{visite}/transferer', [AccueilController::class, 'transferer'])->name('accueil.transferer');
    Route::post('accueil/visites/{visite}/partie', [AccueilController::class, 'partie'])->name('accueil.partie');
});

Route::middleware('can:parcours.constantes')->group(function () {
    Route::post('visites/{visite}/constantes', [AccueilController::class, 'constantes'])->name('accueil.constantes');
});

Route::middleware('can:parcours.file')->group(function () {
    Route::get('file-attente', [FileAttenteController::class, 'index'])->name('file.index');
    Route::post('file-attente/{visite}/appeler', [FileAttenteController::class, 'appeler'])->name('file.appeler');
    Route::post('file-attente/{visite}/terminer', [FileAttenteController::class, 'terminer'])->name('file.terminer');

    // Écran de consultation rapide (lot 3b)
    Route::get('consultations/{consultation}', [ConsultationRapideController::class, 'show'])->name('consultation.show');
    Route::post('consultations/{consultation}/enregistrer', [ConsultationRapideController::class, 'enregistrer'])->name('consultation.enregistrer');
    Route::get('consultations/{consultation}/modeles/{modele}', [ConsultationRapideController::class, 'modele'])->name('consultation.modele');
    Route::post('consultations/{consultation}/modeles', [ConsultationRapideController::class, 'enregistrerModele'])->name('consultation.modeles.store');
    Route::delete('modeles/{modele}', [ConsultationRapideController::class, 'supprimerModele'])->name('modeles.destroy');
});

// ---------------------------------------------------------------- Dossier, grossesse, statistiques (lot 3c)
Route::middleware('can:parcours.dossier')->group(function () {
    Route::get('patients/{patientId}/dossier', [DossierController::class, 'show'])->whereNumber('patientId')->name('dossier.show');
    Route::get('grossesses/{grossesse}', [GrossesseController::class, 'show'])->name('grossesses.show');
    Route::get('grossesses', [GrossesseController::class, 'index'])->name('grossesses.index');
});

Route::middleware('can:parcours.grossesse')->group(function () {
    Route::post('patients/{patientId}/grossesses', [GrossesseController::class, 'store'])->whereNumber('patientId')->name('grossesses.store');
    Route::post('grossesses/{grossesse}/ddr', [GrossesseController::class, 'corrigerDdr'])->name('grossesses.ddr');
    Route::post('grossesses/{grossesse}/cloturer', [GrossesseController::class, 'cloturer'])->name('grossesses.cloturer');
});

Route::middleware('can:parcours.statistiques')->group(function () {
    Route::get('statistiques', [StatistiqueController::class, 'index'])->name('statistiques.index');
});
