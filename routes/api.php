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

Route::prefix('appointments')->group(function () {
    Route::post('/', [AppointmentController::class, 'store']);
    Route::get('/available-dates', [AppointmentController::class, 'getAvailableDates']);
    Route::get('/slots', [AppointmentController::class, 'getAvailableSlots']);
    Route::get('/slots/{id}', [AppointmentController::class, 'getAvailableSlotsByProfessional']);
    Route::get('/slots/{id}/{date}', [AppointmentController::class, 'getAvailableSlotsByProfessionalAndDate']);
    Route::get('/slots/{id}/{date}/{time}', [AppointmentController::class, 'getAvailableSlotsByProfessionalDateAndTime']);
    Route::get('/slots/{id}/{date}/{time}/{duration}', [AppointmentController::class, 'getAvailableSlotsByProfessionalDateTimeAndDuration']);

    Route::post('/{appointment}/confirm', [AppointmentController::class, 'confirmAppointment']);
    Route::post('/{appointment}/cancel', [AppointmentController::class, 'cancelAppointment']);
    Route::post('/{appointment}/reschedule', [AppointmentController::class, 'rescheduleAppointment']);
});

Route::post('check-patient', [AppointmentController::class, 'checkPatient']);
Route::get('departments', [AppointmentController::class, 'getDepartments']);
Route::get('professionals/{id}', [AppointmentController::class, 'getProfessionals']);

/*
|--------------------------------------------------------------------------
| Auth / Patient bootstrap
|--------------------------------------------------------------------------
*/

Route::post('patient/find-or-create', [AuthController::class, 'findOrCreate']);
Route::post('login', [AuthController::class, 'loginWithApi']);
Route::post('register', [AuthController::class, 'register']);
Route::post('logout', [AuthController::class, 'logoutApi'])->middleware('auth:sanctum');
Route::post('check-account', [AuthController::class, 'checkAccountStatus']);

/*
|--------------------------------------------------------------------------
| Doctor availability / admin actions
|--------------------------------------------------------------------------
*/

Route::post('doctors/set-unavailability', [AppointmentController::class, 'setDoctorUnavailability'])
    ->middleware('role:admin,medecin');

/*
|--------------------------------------------------------------------------
| Insurance / payment APIs
|--------------------------------------------------------------------------
*/

Route::get('patient/{patient}/insurances', [Api_PatientInsuranceController::class, 'getPatientInsurances']);
Route::get('transactions/{transaction}/actes', [PaymentController::class, 'getTransactionActes']);
Route::post('insurance/calculate-coverage', [Api_InsuranceCalculationController::class, 'calculateCoverage']);
Route::get('insurance-companies/active', [Api_InsuranceCompanyController::class, 'getActiveCompanies']);
Route::get('patient/{transactionId}/actes', [PatientController::class, 'getPatientActes']);

Route::get('balance/{insurance}', function ($insuranceId) {
    $balance = DB::table('insurance_companies')
        ->select([
            'insurance_companies.id',
            'insurance_companies.name',
            'insurance_companies.code'
        ])
        ->selectRaw('
            COALESCE(SUM(CASE WHEN invoices.insurance_status = "pending" THEN invoices.insurance_amount ELSE 0 END), 0) as montant_du,
            COALESCE(SUM(CASE WHEN invoices.insurance_status = "approved" THEN invoices.insurance_amount ELSE 0 END), 0) as montant_paye
        ')
        ->leftJoin('invoices', 'insurance_companies.id', '=', 'invoices.insurance_company_id')
        ->where('insurance_companies.id', $insuranceId)
        ->groupBy('insurance_companies.id', 'insurance_companies.name', 'insurance_companies.code')
        ->first();

    return response()->json($balance);
})->name('balance');

// Lot 2c : route retirée — la méthode InsuranceBalanceController::pendingInvoices n'existait pas
// (erreur 500 à chaque appel) et l'écran des soldes est remplacé par Assurance > Créances.