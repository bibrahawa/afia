<?php

use App\Http\Controllers\Parcours\AccueilController;
use App\Http\Controllers\Parcours\ConsultationRapideController;
use App\Http\Controllers\Parcours\CroissanceController;
use App\Http\Controllers\Parcours\DocumentMedicalController;
use App\Http\Controllers\Parcours\DossierController;
use App\Http\Controllers\Parcours\SalleAttenteController;
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

    // L'accueil décide qui passe (lot 3d)
    Route::post('accueil/visites/{visite}/prioriser', [AccueilController::class, 'prioriser'])->name('accueil.prioriser');
    Route::post('accueil/visites/{visite}/deplacer', [AccueilController::class, 'deplacer'])->name('accueil.deplacer');
    Route::post('accueil/visites/{visite}/ordre-par-defaut', [AccueilController::class, 'reinitialiserOrdre'])->name('accueil.ordre-defaut');
    Route::post('accueil/ordre-file', [AccueilController::class, 'reglageOrdre'])->name('accueil.reglage-ordre');
});

Route::middleware('can:parcours.constantes')->group(function () {
    Route::post('visites/{visite}/constantes', [AccueilController::class, 'constantes'])->name('accueil.constantes');
});

Route::middleware('can:parcours.file')->group(function () {
    Route::get('file-attente', [FileAttenteController::class, 'index'])->name('file.index');
    Route::post('file-attente/{visite}/appeler', [FileAttenteController::class, 'appeler'])->name('file.appeler');
    Route::post('file-attente/{visite}/terminer', [FileAttenteController::class, 'terminer'])->name('file.terminer');

    // Consultation directe : le médecin reçoit un patient sans passer par l'accueil,
    // par le même chemin (visite créée puis appelée) — remplace « Nouvelle consultation ».
    Route::get('consultations/nouvelle', [\App\Http\Controllers\Parcours\ConsultationDirecteController::class, 'create'])->name('consultation.nouvelle');
    Route::post('consultations/nouvelle', [\App\Http\Controllers\Parcours\ConsultationDirecteController::class, 'store'])->name('consultation.directe');
    Route::get('consultations/patients/recherche', [PatientController::class, 'rechercheRapide'])->name('consultation.patients.recherche');

    // Écran de consultation rapide (lot 3b)
    Route::get('consultations/{consultation}', [ConsultationRapideController::class, 'show'])->name('consultation.show');
    Route::post('consultations/{consultation}/enregistrer', [ConsultationRapideController::class, 'enregistrer'])->name('consultation.enregistrer');
    Route::get('consultations/{consultation}/actes', [ConsultationRapideController::class, 'actes'])->name('consultation.actes');
    Route::post('consultations/{consultation}/constantes', [ConsultationRapideController::class, 'constantes'])->name('consultation.constantes');
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

// ---------------------------------------------------------------- Documents, croissance, salle d'attente (lot 3e)
Route::middleware('can:parcours.dossier')->group(function () {
    Route::get('patients/{patientId}/croissance', [CroissanceController::class, 'show'])->whereNumber('patientId')->name('croissance.show');
    Route::get('patients/{patientId}/documents', [DocumentMedicalController::class, 'index'])->whereNumber('patientId')->name('documents.index');
    Route::get('documents/{document}/imprimer', [DocumentMedicalController::class, 'imprimer'])->name('documents.imprimer');
});

Route::middleware('can:parcours.document')->group(function () {
    Route::get('consultations/{consultation}/documents/modele', [DocumentMedicalController::class, 'modele'])->name('documents.modele');
    Route::post('consultations/{consultation}/documents', [DocumentMedicalController::class, 'store'])->name('documents.store');
    Route::post('documents/{document}/annuler', [DocumentMedicalController::class, 'annuler'])->name('documents.annuler');
});

Route::middleware('can:parcours.accueil')->group(function () {
    Route::get('salle-attente', [SalleAttenteController::class, 'index'])->name('salle-attente.index');
});
