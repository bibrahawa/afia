<?php

use Illuminate\Http\Request;
use App\Http\Controllers\AppointmentController;
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

Route::post('login', [AuthController::class, 'loginWithApi']);
Route::post('register', [AuthController::class, 'register']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
