<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    DashboardController, HospitalController, UserController,
    DepartmentController, ServiceController, EmployeeController,
    PatientController, AppointmentController, PackageController,
    TestController, ReportController, AccountController, AuthController,
    ProfileController, ConsultationController, MedicamentController,
    HospitalisationController, ChambreController
};

// Authentification
Route::get('/', [DashboardController::class, 'index'])->name('rdv');

Route::middleware('guest')->group(function () {
    Route::view('login', 'auth.login')->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login');
});

Route::post('logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {

    // Profile
    // Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    // Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Dashboard
    Route::get('/home', [DashboardController::class, 'admin'])->name('dashboard');
    Route::get('backup', [HospitalController::class, 'backup'])->name('hospital.backup');
    Route::get('setting', [HospitalController::class, 'setting'])->name('hospital.setting');

    // Gestion utilisateurs
    Route::resource('users', UserController::class)->names('users');
    Route::prefix('user')->name('user.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::post('edit', [UserController::class, 'edit'])->name('edit');
        Route::post('delete', [UserController::class, 'delete'])->name('delete');
    });
    Route::get('disable-user/{id}', [UserController::class, 'disableUser'])->name('user.disable');
    Route::get('index-permissions', [UserController::class, 'indexPermissions'])->name('users.index_permissions');
    Route::get('liste-permissions/{id}', [UserController::class, 'listePermissions'])->name('users.listePermissions');
    Route::post('assign-permissions/{id}', [UserController::class, 'assignPermissions'])->name('users.store_permissions');
    Route::post('change/password', [UserController::class, 'changePassword'])->name('change.password');

    // Hôpital configuration
    Route::prefix('hospital')->group(function () {
        Route::post('update/{id}', [HospitalController::class, 'updateHospital'])->name('hospital.update');
        Route::post('tax/update/{id}', [HospitalController::class, 'updateTax'])->name('tax.update');
        Route::post('config/update', [HospitalController::class, 'updateConfig'])->name('config.update');
    });

    // Employés
    Route::resource('employee', EmployeeController::class);
    Route::get('employee/profile', [EmployeeController::class, 'profile'])->name('employee.profile');

    Route::prefix('medecin')->name('medecin.')->group(function () {

        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('appointments', [DashboardController::class, 'appointments'])->name('appointments');
        Route::post('appointments/{id}/confirm', [DashboardController::class, 'confirmAppointment'])->name('appointments.confirm');
        Route::post('appointments/{id}/complete', [DashboardController::class, 'completeAppointment'])->name('appointments.complete');

        Route::get('availabilities', [DashboardController::class, 'availabilities'])->name('availabilities');
        Route::post('availabilities', [DashboardController::class, 'storeAvailability'])->name('availabilities.store');
        Route::put('availabilities', [DashboardController::class, 'updateAvailability'])->name('availabilities.update');
        Route::delete('availabilities/{id}', [DashboardController::class, 'destroyAvailability'])->name('availabilities.destroy');

        Route::get('leaves', [DashboardController::class, 'leaves'])->name('leaves');
        Route::post('leaves', [DashboardController::class, 'storeLeave'])->name('leaves.store');
        Route::put('leaves/update', [DashboardController::class, 'updateLeave'])->name('leaves.update');
        Route::delete('leaves', [DashboardController::class, 'destroyLeaves'])->name('leaves.destroy');
    });

    // Départements
    Route::prefix('department')->name('department.')->group(function () {
        Route::get('/', [DepartmentController::class, 'getIndex'])->name('index');
        Route::post('add', [DepartmentController::class, 'store'])->name('add');
        Route::post('update', [DepartmentController::class, 'update'])->name('update');
        Route::delete('delete/{id?}', [DepartmentController::class, 'delete'])->name('delete');
    });

    // Services
    Route::prefix('service')->name('service.')->group(function () {
        Route::get('/', [ServiceController::class, 'getIndex'])->name('index');
        Route::post('add', [ServiceController::class, 'store'])->name('add');
        Route::post('update', [ServiceController::class, 'update'])->name('update');
        Route::delete('delete/{id?}', [ServiceController::class, 'delete'])->name('delete');
    });

    // Patients & Consultations
    Route::resource('patient', PatientController::class);
    Route::post('patient/file/{id}', [PatientController::class, 'addFile'])->name('patient.addFile');
    Route::resource('consultation', ConsultationController::class);
    Route::post('consultations/{consultation}/facturer', [ConsultationController::class, 'facturer'])->name('consultations.facturer');

    // Factures de consultations
    Route::prefix('consultations/{id}/facture')->name('consultations.facture.')->group(function () {
        Route::get('/', [ConsultationController::class, 'facture'])->name('');
        Route::get('/ordonnance', [ConsultationController::class, 'facture_ordonnance'])->name('ordonnance');
        Route::get('/medicament', [ConsultationController::class, 'facture_medicament'])->name('medicament');
        Route::get('/paiement', [ConsultationController::class, 'facture_paiement'])->name('paiement');
        Route::get('/examen', [ConsultationController::class, 'facture_examen'])->name('examen');
    });
    Route::view('/facture', 'consultations.facture.facture_consultation')->name('facture.consultation');

    // Rendez-vous
    Route::resource('appointment', AppointmentController::class);
    Route::post('appointment/updated', [AppointmentController::class, 'updated'])->name('appointment.updated');

    // Médicaments
    Route::resource('medicaments', MedicamentController::class);

    // Packages
    Route::prefix('package')->name('package.')->group(function () {
        Route::get('/', [PackageController::class, 'getIndex'])->name('index');
        Route::post('/', [PackageController::class, 'store'])->name('store');
        Route::put('update/{id}', [PackageController::class, 'update'])->name('update');
        Route::get('/edit/{id}', [PackageController::class, 'edit'])->name('edit');
        Route::post('test/delete', [PackageController::class, 'packageTestDelete'])->name('test.delete');
        Route::delete('delete/{id}', [PackageController::class, 'delete'])->name('delete');
        Route::post('sale', [PackageController::class, 'packageSale'])->name('sale');
        Route::get('sale/{id}', [PackageController::class, 'packageSales'])->name('sales');
    });

    // Tests
    Route::prefix('test')->name('test.')->group(function () {
        Route::get('/', [TestController::class, 'index'])->name('index');
        Route::post('{id}/status', [TestController::class, 'statusChange'])->name('status');
        Route::post('add', [TestController::class, 'store'])->name('store');
        Route::post('edit', [TestController::class, 'edit'])->name('edit');
        Route::delete('delete', [TestController::class, 'delete'])->name('delete');
    });

    // Comptabilité
    Route::prefix('account')->name('account.')->group(function () {
        Route::get('facture', [AccountController::class, 'factureNonPayer'])->name('facture');
        Route::post('facture', [AccountController::class, 'payer'])->name('payer');
        Route::get('service', [AccountController::class, 'serviceReport'])->name('service');
        Route::get('opd', [AccountController::class, 'opdReport'])->name('opd');
        Route::get('package', [AccountController::class, 'packageReport'])->name('package');
    });

    // Rapports
    Route::get('report', [ReportController::class, 'index'])->name('reports.index');
    Route::post('service/report', [ReportController::class, 'service'])->name('service.report');

    // Hospitalisations & chambres
    Route::resource('hospitalisations', HospitalisationController::class)->except('update');
    Route::put('hospitalisation/update', [HospitalisationController::class, 'update'])->name('hospitalisation.update');
    Route::get('hospitalisations/{hospitalisation}/payer', [HospitalisationController::class, 'payer'])->name('hospitalisations.payer');
    Route::get('hospitalisations/{hospitalisation}/facture', [HospitalisationController::class, 'facture'])->name('hospitalisations.facture');

    Route::resource('chambres', ChambreController::class)->except('update');
    Route::put('chambre/update', [ChambreController::class, 'update'])->name('chambre.update');
});

// require __DIR__.'/auth.php';

