<?php

use App\Http\Controllers\Rapports\RapportController;
use Illuminate\Support\Facades\Route;

/*
| Préfixe : /rapports — Noms : rapports.* — Permission : rapports.view
*/

Route::middleware('can:rapports.view')->group(function () {
    Route::get('/', [RapportController::class, 'index'])->name('index');
    Route::get('{rapport}', [RapportController::class, 'show'])->name('show');
    Route::get('{rapport}/csv', [RapportController::class, 'csv'])->name('csv');
});
