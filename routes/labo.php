<?php

use App\Http\Controllers\Labo\CatalogueController;
use App\Http\Controllers\Labo\CompteRenduController;
use App\Http\Controllers\Labo\DemandeController;
use App\Http\Controllers\Labo\PaillasseController;
use App\Http\Controllers\Labo\PrelevementController;
use App\Http\Controllers\Labo\ReceptionController;
use App\Http\Controllers\Labo\TableauBordController;
use App\Http\Controllers\Labo\ValidationController;
use Illuminate\Support\Facades\Route;

/*
| Préfixe : /laboratoire — Noms : labo.* — Middleware : web, auth, module:laboratoire
| (voir LaboServiceProvider). Paramètres nommés laboXxx pour ne jamais
| entrer en collision avec un {demande} ou {examen} existant ailleurs.
*/

Route::get('/', [TableauBordController::class, 'index'])->name('tableau-bord')->middleware('can:labo.tableau_bord');

// ---------------------------------------------------------------- Catalogue
Route::prefix('catalogue')->name('catalogue.')->group(function () {
    Route::get('/', [CatalogueController::class, 'index'])->name('index')->middleware('can:labo.catalogue.view');

    Route::middleware('can:labo.catalogue.manage')->group(function () {
        Route::post('importer-modele', [CatalogueController::class, 'importerModele'])->name('importer');
        Route::post('sections', [CatalogueController::class, 'storeSection'])->name('sections.store');
        Route::get('examens/creer', [CatalogueController::class, 'create'])->name('examens.create');
        Route::post('examens', [CatalogueController::class, 'store'])->name('examens.store');
        Route::get('examens/{laboExamen}/modifier', [CatalogueController::class, 'edit'])->name('examens.edit');
        Route::put('examens/{laboExamen}', [CatalogueController::class, 'update'])->name('examens.update');
        Route::post('examens/{laboExamen}/basculer', [CatalogueController::class, 'basculer'])->name('examens.basculer');
    });
});

// ---------------------------------------------------------------- Demandes
Route::prefix('demandes')->name('demandes.')->group(function () {
    Route::get('/', [DemandeController::class, 'index'])->name('index')->middleware('can:labo.demande.view');

    Route::middleware('can:labo.demande.create')->group(function () {
        Route::get('creer', [DemandeController::class, 'create'])->name('create');
        Route::post('/', [DemandeController::class, 'store'])->name('store');
        Route::post('depuis-consultation/{consultation}', [DemandeController::class, 'depuisConsultation'])->name('depuis-consultation');
    });

    Route::get('{laboDemande}', [DemandeController::class, 'show'])->name('show')->middleware('can:labo.demande.view');

    Route::middleware('can:labo.demande.cancel')->group(function () {
        Route::post('{laboDemande}/annuler', [DemandeController::class, 'annuler'])->name('annuler');
        Route::post('examens/{laboLigne}/annuler', [DemandeController::class, 'annulerExamen'])->name('examens.annuler');
    });

    Route::middleware('can:labo.facturation')->group(function () {
        Route::post('{laboDemande}/facturer', [DemandeController::class, 'facturer'])->name('facturer');
    });

    Route::post('{laboDemande}/sms-resultats', [CompteRenduController::class, 'renvoyerSms'])->name('sms')->middleware('can:labo.compte_rendu.publier');
    Route::post('{laboDemande}/publier', [CompteRenduController::class, 'publier'])->name('publier')->middleware('can:labo.compte_rendu.publier');
    Route::get('{laboDemande}/etiquettes', [PrelevementController::class, 'etiquettes'])->name('etiquettes')->middleware('can:labo.prelevement');
});

// ---------------------------------------------------------------- Pré-analytique
Route::middleware('can:labo.prelevement')->group(function () {
    Route::get('prelevements', [PrelevementController::class, 'index'])->name('prelevements.index');
    Route::post('echantillons/{laboEchantillon}/preleve', [PrelevementController::class, 'preleve'])->name('echantillons.preleve');
});

Route::middleware('can:labo.reception')->group(function () {
    Route::get('reception', [ReceptionController::class, 'index'])->name('reception.index');
    Route::post('reception/scanner', [ReceptionController::class, 'scanner'])->name('reception.scanner');
    Route::post('echantillons/{laboEchantillon}/recu', [ReceptionController::class, 'recu'])->name('echantillons.recu');
    Route::post('echantillons/{laboEchantillon}/rejeter', [ReceptionController::class, 'rejeter'])->name('echantillons.rejeter');
    Route::post('lignes/{laboLigne}/envoi-sous-traitance', [ReceptionController::class, 'envoiSousTraitance'])->name('lignes.sous-traitance');
});

// ---------------------------------------------------------------- Analytique
Route::middleware('can:labo.resultat.saisir')->prefix('paillasse')->name('paillasse.')->group(function () {
    Route::get('/', [PaillasseController::class, 'index'])->name('index');
    Route::get('{laboLigne}', [PaillasseController::class, 'saisie'])->name('saisie');
    Route::post('{laboLigne}', [PaillasseController::class, 'enregistrer'])->name('enregistrer');
    Route::post('{laboLigne}/bacteriologie', [PaillasseController::class, 'enregistrerBacteriologie'])->name('bacteriologie');
});

// ---------------------------------------------------------------- Post-analytique
Route::prefix('validation')->name('validation.')->group(function () {
    Route::get('/', [ValidationController::class, 'index'])->name('index')->middleware('can:labo.validation.technique');
    Route::post('{laboLigne}/technique', [ValidationController::class, 'technique'])->name('technique')->middleware('can:labo.validation.technique');

    Route::middleware('can:labo.validation.biologique')->group(function () {
        Route::post('{laboLigne}/biologique', [ValidationController::class, 'biologique'])->name('biologique');
        Route::post('{laboLigne}/complete', [ValidationController::class, 'complete'])->name('complete');
        Route::post('{laboLigne}/renvoyer', [ValidationController::class, 'renvoyer'])->name('renvoyer');
        Route::post('{laboLigne}/rouvrir', [ValidationController::class, 'rouvrir'])->name('rouvrir');
    });

    // Un technicien de garde peut, lui aussi, tracer l'appel d'une valeur critique.
    Route::post('resultats/{laboResultat}/alerte-critique', [ValidationController::class, 'alerteCritique'])
        ->name('alerte-critique')->middleware('can:labo.resultat.saisir');
});

Route::get('comptes-rendus/{laboCompteRendu}/pdf', [CompteRenduController::class, 'pdf'])->name('comptes-rendus.pdf')->middleware('can:labo.compte_rendu.view');
