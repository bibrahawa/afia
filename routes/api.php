<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Api\Api_PatientInsuranceController;
use App\Http\Controllers\Api\Api_InsuranceCalculationController;
use App\Http\Controllers\Api\Api_InsuranceCompanyController;

/*
|--------------------------------------------------------------------------
| Appointment API
|--------------------------------------------------------------------------
*/

/*
| RETIRÉ le 2026-10-10 : ces routes pointaient vers des méthodes absentes du
| contrôleur (erreur 500 à l'appel). La prise de rendez-vous publique passe
| désormais par les routes nommées de routes/web.php, qui portent le contexte
| de l'établissement (/{etablissement}/rendez-vous/...).
|
|   appointments/slots, /slots/{id}, /slots/{id}/{date}, /slots/{id}/{date}/{time},
|   /slots/{id}/{date}/{time}/{duration}, {appointment}/confirm, /cancel, /reschedule,
|   departments, professionals/{id}
|
| Conservées ci-dessous : celles dont la méthode existe réellement.
*/

Route::prefix('appointments')->middleware('throttle:20,1')->group(function () {
    Route::post('/', [AppointmentController::class, 'store']);
    Route::get('/available-dates', [AppointmentController::class, 'getAvailableDates']);
});

Route::post('check-patient', [AppointmentController::class, 'checkPatient'])->middleware('throttle:10,1');

/*
|--------------------------------------------------------------------------
| Auth / Patient bootstrap
|--------------------------------------------------------------------------
*/

Route::post('patient/find-or-create', [AuthController::class, 'findOrCreate'])->middleware('throttle:10,1');
Route::post('login', [AuthController::class, 'loginWithApi'])->middleware('throttle:10,1');
Route::post('register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('logout', [AuthController::class, 'logoutApi'])->middleware('auth:sanctum');
Route::post('check-account', [AuthController::class, 'checkAccountStatus'])->middleware('throttle:10,1');

/*
|--------------------------------------------------------------------------
| Doctor availability / admin actions
|--------------------------------------------------------------------------
*/

/*
| RETIRÉ le 2026-10-10 : AppointmentController::setDoctorUnavailability n'existe
| plus. L'indisponibilité d'un médecin se gère par les congés et absences du
| back-office, qui déclenchent ProcessDoctorUnavailabilityJob.
*/

/*
|--------------------------------------------------------------------------
| Insurance / payment APIs
|--------------------------------------------------------------------------
*/

/*
| DÉSACTIVÉ (revue de sécurité, lot R) — routes accessibles SANS connexion, qui renvoyaient
| les données de TOUTES les cliniques (aucun utilisateur connecté = aucun cloisonnement) :
| assurances d'un patient, actes facturés (donc soins reçus), montants dus par un assureur,
| liste des organismes. Aucun écran actif ne les appelle (les anciens écrans d'impayés
| sont remplacés par la caisse). À réactiver uniquement derrière une authentification
| par jeton, et avec cloisonnement par établissement.
|
| Route::get('patient/{patient}/insurances', [Api_PatientInsuranceController::class, 'getPatientInsurances']);
| Route::get('transactions/{transaction}/actes', [PaymentController::class, 'getTransactionActes']);
| Route::post('insurance/calculate-coverage', [Api_InsuranceCalculationController::class, 'calculateCoverage']);
| Route::get('insurance-companies/active', [Api_InsuranceCompanyController::class, 'getActiveCompanies']);
| Route::get('patient/{transactionId}/actes', [PatientController::class, 'getPatientActes']);
| 
| Route::get('balance/{insurance}', function ($insuranceId) {
|     $balance = DB::table('insurance_companies')
|         ->select([
|             'insurance_companies.id',
|             'insurance_companies.name',
|             'insurance_companies.code'
|         ])
|         ->selectRaw('
|             COALESCE(SUM(CASE WHEN invoices.insurance_status = "pending" THEN invoices.insurance_amount ELSE 0 END), 0) as montant_du,
|             COALESCE(SUM(CASE WHEN invoices.insurance_status = "approved" THEN invoices.insurance_amount ELSE 0 END), 0) as montant_paye
|         ')
|         ->leftJoin('invoices', 'insurance_companies.id', '=', 'invoices.insurance_company_id')
|         ->where('insurance_companies.id', $insuranceId)
|         ->groupBy('insurance_companies.id', 'insurance_companies.name', 'insurance_companies.code')
|         ->first();
| 
|     return response()->json($balance);
| })->name('balance');
*/

// Lot 2c : route retirée — la méthode InsuranceBalanceController::pendingInvoices n'existait pas
// (erreur 500 à chaque appel) et l'écran des soldes est remplacé par Assurance > Créances.