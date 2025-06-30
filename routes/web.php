<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HospitalController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\OpdController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\ReferenceTestController;
use App\Http\Controllers\MicrobiologyController;
use App\Http\Controllers\HaematologyController;
use App\Http\Controllers\ImmunologyController;
use App\Http\Controllers\ExaminationController;
use App\Http\Controllers\BiochemistryController;
use App\Http\Controllers\StainController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\DoctorApiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\MedicamentController;
use App\Http\Controllers\HospitalisationController;
use App\Http\Controllers\ChambreController;


Route::resource('users', UserController::class)->names('users');
Route::get('disable-user/{id}',[UserController::class,'disableUser'])->name('user.disable');
Route::get('index-permissions', [UserController::class, 'indexPermissions'])->name('users.index_permissions');
Route::get('liste-permissions/{id}', [UserController::class, 'listePermissions'])->name('users.listePermissions');
Route::post('assign-permissions/{id}', [UserController::class, 'assignPermissions'])->name('users.store_permissions');


Route::get('val', function(){
    return view('dashboard');
});

// Routes d'authentification
// Authentication Routes

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';


Route::middleware('guest')->group(function () {
    Route::get('login', fn() => view('auth.login'))->name('login');
    Route::get('register', fn() => view('auth.register'))->name('register');
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
});

Route::post('logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Routes API et publiques
Route::get('/invoice/calculate/', [InvoiceController::class, 'calculate']);
Route::get('/days/{id}', [DoctorApiController::class, 'getDays']);

// Routes protégées par authentification
Route::middleware(['auth'])->group(function() {
    // Dashboard et paramètres
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('backup', [HospitalController::class, 'backup'])->name('hospital.backup');
    Route::get('setting', [HospitalController::class, 'setting'])->name('hospital.setting');

    // Routes de configuration
    Route::post('change/password', [UserController::class, 'changePassword'])->name('change.password');
    Route::post('hospital/update/{id}', [HospitalController::class, 'updateHospital'])->name('hospital.update');
    Route::post('tax/update/{id}', [HospitalController::class, 'updateTax'])->name('tax.update');
    Route::post('config/update/', [HospitalController::class, 'updateConfig'])->name('config.update');

    // Gestion des utilisateurs
    Route::name('user.')->group(function() {
        Route::get('user', [UserController::class, 'index'])->name('index');
        Route::post('user', [UserController::class, 'store'])->name('store');
        Route::post('user/edit', [UserController::class, 'edit'])->name('edit');
        Route::post('user/delete', [UserController::class, 'delete'])->name('delete');
    });

    // Gestion des départements
    Route::name('department.')->group(function() {
        Route::get('department', [DepartmentController::class, 'getIndex'])->name('index');
        Route::post('department/add', [DepartmentController::class, 'store'])->name('add');
        Route::post('department/update', [DepartmentController::class, 'update'])->name('update');
        Route::delete('department/delete/{id?}', [DepartmentController::class, 'delete'])->name('delete');
    });

    // Gestion des services
    Route::name('service.')->group(function() {
        Route::get('service', [ServiceController::class, 'getIndex'])->name('index');
        Route::post('service/add', [ServiceController::class, 'store'])->name('add');
        Route::post('service/update', [ServiceController::class, 'update'])->name('update');
        Route::delete('service/delete/{id?}', [ServiceController::class, 'delete'])->name('delete');
    });

    // Resources
    Route::resource('employee', EmployeeController::class);
    Route::get('employee/profile', [EmployeeController::class, 'profile'])->name('employee.profile');
    Route::resource('doctor', DoctorController::class);
    Route::resource('patient', PatientController::class);
    Route::post('patient/file/{id}', [PatientController::class, 'addFile'])->name('patient.addFile');
    Route::resource('appointment', AppointmentController::class);
    Route::resource('role', RoleController::class);


    Route::resource('/consultation', ConsultationController::class);
    Route::resource('medicaments', MedicamentController::class);
    Route::post('/consultations/{consultation}/facturer', [ConsultationController::class, 'facturer'])->name('consultations.facturer');

    // Les factures de consultations
    Route::get('/consultations/{id}/facture', [ConsultationController::class, 'facture'])->name('consultations.facture');
    Route::get('/consultations/{id}/facture/ordonnance', [ConsultationController::class, 'facture_ordonnance'])->name('consultations.facture.ordonnance');
    Route::get('/consultations/{id}/facture/medicament', [ConsultationController::class, 'facture_medicament'])->name('consultations.facture.medicament');
    Route::get('/consultations/{id}/facture/paiement', [ConsultationController::class, 'facture_paiement'])->name('consultations.facture.paiement');
    Route::get('/consultations/{id}/facture/examen', [ConsultationController::class, 'facture_examen'])->name('consultations.facture.examen');

    // Route::get('/patients/{patient}/consultations', [ConsultationController::class, 'index'])->name('consultations.index');
    // Route::get('/patients/{patient}/consultations/create', [ConsultationController::class, 'create'])->name('consultations.create');
    // Route::post('/consultations', [ConsultationController::class, 'store'])->name('consultations.store');


    // Gestion des rendez-vous
    Route::post('appointment/updated', [AppointmentController::class, 'updated'])->name('appointment.updated');

    // Gestion des packages
    Route::name('package.')->group(function() {
        Route::get('package', [PackageController::class, 'getIndex'])->name('index');
        Route::post('package', [PackageController::class, 'store'])->name('store');
        Route::post('package/update', [PackageController::class, 'update'])->name('update');
        Route::post('package/test/delete', [PackageController::class, 'packageTestDelete'])->name('test.delete');
        Route::delete('package/delete/{id}', [PackageController::class, 'delete'])->name('delete');
        Route::post('package/sale', [PackageController::class, 'packageSale'])->name('sale');
        // Route::get('package/sale', [PackageController::class, 'sale'])->name('sale');
        Route::get('package/sale/{id}', [PackageController::class, 'packageSales'])->name('sales');
    });

    Route::get('/get-services-by-department/{departmentId}', [PackageController::class, 'getServicesByDepartment'])->name('get.services.by.department');
    // You might also need a similar route for tests if you want to filter them too.
    Route::get('/get-tests-by-department/{departmentId}', [PackageController::class, 'getTestsByDepartment'])->name('get.tests.by.department');

    // Gestion des factures
    Route::name('invoice.')->group(function() {
        Route::get('invoice', [InvoiceController::class, 'index'])->name('index');
        Route::post('invoice', [InvoiceController::class, 'store'])->name('store');
        Route::get('invoice/patient/{patient_id}', [InvoiceController::class, 'patient'])->name('patient');
        Route::get('invoice/remove/{id}', [InvoiceController::class, 'remove'])->name('remove');
        Route::get('invoice/sales/{id}', [InvoiceController::class, 'tempSales'])->name('sale');
        Route::get('invoice/opd/{id}', [OpdController::class, 'opdSales'])->name('opd');
        Route::get('invoice/report', [InvoiceController::class, 'report'])->name('report');
        Route::get('invoice/duplicate/{id}', [InvoiceController::class, 'duplicate'])->name('duplicate');
        Route::post('invoice/return', [InvoiceController::class, 'invoiceReturn'])->name('return');
    });

    Route::get('search/invoice', [InvoiceController::class, 'searchInvoice'])->name('search.invoice');

    // Gestion OPD
    Route::name('opd.')->group(function() {
        Route::get('opd', [OpdController::class, 'getindex'])->name('index');
        Route::post('opd', [OpdController::class, 'store'])->name('store');
    });

    // Gestion des tests
    Route::name('test.')->group(function() {
        Route::get('test', [TestController::class, 'index'])->name('index');
        Route::post('test/{id}/status', [TestController::class, 'statusChange'])->name('status');
        Route::post('test/add', [TestController::class, 'store'])->name('store');
        Route::post('test/edit', [TestController::class, 'edit'])->name('edit');
        Route::post('test/delete', [TestController::class, 'delete'])->name('delete');
    });

    // Gestion des références
    Route::name('reference.')->group(function() {
        Route::get('reference', [ReferenceTestController::class, 'index'])->name('index');
        Route::post('reference', [ReferenceTestController::class, 'store'])->name('store');
        Route::post('reference/update', [ReferenceTestController::class, 'edit'])->name('update');
        Route::post('reference/delete', [ReferenceTestController::class, 'delete'])->name('delete');
    });

    // Microbiologie
    Route::post('antibiotic', [MicrobiologyController::class, 'storeAntibiotic'])->name('antibiotic.store');
    Route::post('antibiotic/edit', [MicrobiologyController::class, 'editAntibiotic'])->name('antibiotic.edit');
    Route::get('microbiology', [MicrobiologyController::class, 'index'])->name('microbiology.index');
    Route::post('microbiology', [MicrobiologyController::class, 'store'])->name('microbiology.store');

    // Autres départements médicaux
    Route::get('haematology', [HaematologyController::class, 'index'])->name('haematology.index');
    Route::post('haematology', [HaematologyController::class, 'store'])->name('haematology.store');

    Route::get('immunology', [ImmunologyController::class, 'index'])->name('immunology.index');
    Route::post('immunology', [ImmunologyController::class, 'store'])->name('immunology.store');

    Route::name('examination.')->group(function() {
        Route::get('examination', [ExaminationController::class, 'index'])->name('index');
        Route::post('examination', [ExaminationController::class, 'store'])->name('store');
        Route::post('examination/update', [ExaminationController::class, 'update'])->name('update');
    });

    Route::get('biochemistry', [BiochemistryController::class, 'index'])->name('biochemistry.index');
    Route::post('biochemistry', [BiochemistryController::class, 'store'])->name('biochemistry.store');

    Route::name('stain.')->group(function() {
        Route::get('stain', [StainController::class, 'index'])->name('index');
        Route::post('stain', [StainController::class, 'store'])->name('store');
        Route::post('stain/edit', [StainController::class, 'edit'])->name('edit');
    });

    // Rapports
    Route::name('report.')->group(function() {
        Route::get('report', [ReportController::class, 'index'])->name('index');
        Route::post('report/{id}/status', [ReportController::class, 'statusChange'])->name('status');
        Route::get('report/edit/{id}', [ReportController::class, 'edit'])->name('edit');
        Route::get('report/patient/{patient_id}', [ReportController::class, 'patient'])->name('patient');
        Route::post('report/update', [ReportController::class, 'update'])->name('update');
        Route::get('report/print/{id}', [ReportController::class, 'printReport'])->name('print');
        Route::get('report/result/edit', [ReportController::class, 'editResult'])->name('result');
        Route::get('generate/report/{report_id}', [ResultController::class, 'generateReport'])->name('generate');
    });

    // Résultats
    Route::name('result.')->group(function() {
        Route::get('result/test/{id}', [ResultController::class, 'getTest'])->name('test');
        Route::get('result/tests/{id}', [ResultController::class, 'getTests'])->name('tests');
        Route::post('result', [ResultController::class, 'store'])->name('store');
        Route::post('result/edit', [ResultController::class, 'edit'])->name('edit');
        Route::get('result/comment/edit', [ResultController::class, 'editComment'])->name('comment');
    });

    // Comptabilité
    Route::name('account.')->group(function() {
        Route::get('account/facture', [AccountController::class, 'factureNonPayer'])->name('facture');
        Route::post('account/facture', [AccountController::class, 'payer'])->name('payer');

        Route::get('account/service', [AccountController::class, 'serviceReport'])->name('service');
        Route::get('account/opd', [AccountController::class, 'opdReport'])->name('opd');
        Route::get('account/package', [AccountController::class, 'packageReport'])->name('package');
    });

    // RAPPORTS
    Route::get('report', [ReportController::class, 'index'])->name('reports.index');
    Route::post('service/report', [ReportController::class, 'service'])->name('service.report');

    Route::get('/facture', function(){
        return view('consultations.facture.facture_consultation');
    })->name('facture.consultation');

    Route::resource('hospitalisations', HospitalisationController::class);
    Route::resource('chambres', ChambreController::class);

    Route::get('/hospitalisations/{hospitalisation}/payer', [
        HospitalisationController::class, 'payer'
    ])->name('hospitalisations.payer');

    Route::get('/hospitalisations/{hospitalisation}/facture', [
        HospitalisationController::class, 'facture'
    ])->name('hospitalisations.facture');


});
