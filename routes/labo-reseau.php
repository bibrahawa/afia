<?php

use App\Http\Controllers\Labo\DemandeExterneController;
use Illuminate\Support\Facades\Route;

/*
| Préfixe : /laboratoire-reseau — Noms : labo.reseau.* — Middleware : web, auth
| Côté CLINIQUE prescriptrice : elle n'a pas le module laboratoire, seulement
| un partenariat avec un laboratoire (voir LaboServiceProvider).
*/

Route::middleware('can:labo.reseau.view')->group(function () {
    Route::get('demandes', [DemandeExterneController::class, 'index'])->name('index');
    Route::get('demandes/{demande}', [DemandeExterneController::class, 'show'])->whereNumber('demande')->name('show');
    Route::get('demandes/{demande}/compte-rendu', [DemandeExterneController::class, 'compteRendu'])->whereNumber('demande')->name('compte-rendu');
    Route::get('demandes/{demande}/bon', [DemandeExterneController::class, 'bon'])->whereNumber('demande')->name('bon');
    // Facturation reçue du laboratoire (lot 4b)
    Route::get('factures', [DemandeExterneController::class, 'factures'])->name('factures');
    Route::get('factures/{releve}', [DemandeExterneController::class, 'facture'])->whereNumber('releve')->name('facture');
    Route::get('propositions', [DemandeExterneController::class, 'propositions'])->name('propositions');
    Route::post('propositions/{partenariat}/accepter', [DemandeExterneController::class, 'accepter'])->whereNumber('partenariat')->name('propositions.accepter');
    Route::post('propositions/{partenariat}/refuser', [DemandeExterneController::class, 'refuser'])->whereNumber('partenariat')->name('propositions.refuser');
    Route::get('correspondances', [DemandeExterneController::class, 'correspondances'])->name('correspondances');
    Route::post('correspondances', [DemandeExterneController::class, 'majCorrespondance'])->name('correspondances.update');
});

Route::middleware('can:labo.reseau.demander')->group(function () {
    Route::get('envoyer', [DemandeExterneController::class, 'create'])->name('create');
    Route::post('envoyer', [DemandeExterneController::class, 'store'])->name('store');
    Route::get('patients/recherche', [DemandeExterneController::class, 'patients'])->name('patients.recherche');
    Route::post('demandes/{demande}/annuler', [DemandeExterneController::class, 'annuler'])->whereNumber('demande')->name('annuler');
});
