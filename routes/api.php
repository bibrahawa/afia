<?php

use Illuminate\Http\Request;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Api\Api_PatientInsuranceController;
use App\Http\Controllers\Api\Api_InsuranceCalculationController;
use App\Http\Controllers\Api\Api_InsuranceCompanyController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\InsuranceBalanceController;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::get('departments', [AppointmentController::class, 'getDepartments']);
Route::get('professionals/{id}', [AppointmentController::class, 'getProfessionals']);
Route::get('appointments/slots', [AppointmentController::class, 'getAvailableSlots']);

Route::get('appointments/slots/{id}', [AppointmentController::class, 'getAvailableSlotsByProfessional']);
Route::get('appointments/slots/{id}/{date}', [AppointmentController::class, 'getAvailableSlotsByProfessionalAndDate']);
Route::get('appointments/slots/{id}/{date}/{time}', [AppointmentController::class, 'getAvailableSlotsByProfessionalDateAndTime']);
Route::get('appointments/slots/{id}/{date}/{time}/{duration}', [AppointmentController::class, 'getAvailableSlotsByProfessionalDateTimeAndDuration']);
Route::get('available-slots', [AppointmentController::class, 'getAvailableSlots']);
Route::post('appointments', [AppointmentController::class, 'store']);

// Gestion des rendez-vous
Route::post('/appointments/{appointment}/confirm', [AppointmentController::class, 'confirmAppointment']);
Route::post('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancelAppointment']);
Route::post('/appointments/{appointment}/reschedule', [AppointmentController::class, 'rescheduleAppointment']);

// Indisponibilité médecin (admin/médecins seulement)
Route::post('/doctors/set-unavailability', [AppointmentController::class, 'setDoctorUnavailability'])
    ->middleware('role:admin,doctor');


Route::post('login', [AuthController::class, 'loginWithApi']);
Route::post('register', [AuthController::class, 'register']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');

// API pour récupérer les assurances d'un patient
Route::get('/patient/{patient}/insurances', [Api_PatientInsuranceController::class, 'getPatientInsurances']);

// API pour calculer la couverture d'assurance
Route::post('/insurance/calculate-coverage', [Api_InsuranceCalculationController::class, 'calculateCoverage']);

// API pour les compagnies d'assurance actives
Route::get('/insurance-companies/active', [Api_InsuranceCompanyController::class, 'getActiveCompanies']);

// API pour vérifier la validité d'une police d'assurance
// Route::post('/insurance/verify-policy', [Api_InsuranceVerificationController::class, 'verifyPolicy']);
// Récupérer les actes médicaux d'un patient
Route::get('/patient/{transactionId}/actes', [PatientController::class, 'getPatientActes']);

// Obtenir le solde d'une assurance spécifique
Route::get('/balance/{insurance}', function($insuranceId) {
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

// Obtenir les factures impayées d'une assurance
Route::get('insurance/pending-invoices/{insurance}',[InsuranceBalanceController::class, 'pendingInvoices'])->name('pending-invoices');
    


