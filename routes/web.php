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
use App\Models\Service;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route publique pour le calcul du montant
Route::get('/amount', function() {
    $amount = Service::get();

    foreach($amount as $amt) {
        $data["amount"] = $amt->amount * 20 / 21;
        $amt->update($data);
    }

    return 'Complete';
});

Route::get('val', function(){
    $user = auth()->user();
    $hasPermission = $user->can('search.invoice');
    dd($hasPermission, $user->permissions->pluck('name'));
});

// Routes d'authentification
// Authentication Routes
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
        Route::post('department/edit', [DepartmentController::class, 'edit'])->name('edit');
        Route::post('department/delete', [DepartmentController::class, 'delete'])->name('delete');
    });

    // Gestion des services
    Route::name('service.')->group(function() {
        Route::get('service', [ServiceController::class, 'getIndex'])->name('index');
        Route::post('service/add', [ServiceController::class, 'store'])->name('add');
        Route::post('service/edit', [ServiceController::class, 'edit'])->name('edit');
        Route::post('service/delete', [ServiceController::class, 'delete'])->name('delete');
    });

    // Resources
    Route::resource('employee', EmployeeController::class);
    Route::resource('doctor', DoctorController::class);
    Route::resource('patient', PatientController::class);
    Route::resource('appointment', AppointmentController::class);
    Route::resource('role', RoleController::class);

    // Gestion des rendez-vous
    Route::post('appointment/updated', [AppointmentController::class, 'updated'])->name('appointment.updated');

    // Gestion des packages
    Route::name('package.')->group(function() {
        Route::get('package', [PackageController::class, 'getIndex'])->name('index');
        Route::post('package', [PackageController::class, 'store'])->name('store');
        Route::post('package/edit', [PackageController::class, 'edit'])->name('edit');
        Route::post('package/test/delete', [PackageController::class, 'packageTestDelete'])->name('test.delete');
        Route::post('pacakge/delete', [PackageController::class, 'delete'])->name('delete');
        Route::post('package/sale', [PackageController::class, 'packageSale'])->name('sale');
        // Route::get('package/sale', [PackageController::class, 'sale'])->name('sale');
        Route::get('package/sale/{id}', [PackageController::class, 'packageSales'])->name('sales');
    });

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
        Route::get('account/service', [AccountController::class, 'serviceReport'])->name('service');
        Route::get('account/opd', [AccountController::class, 'opdReport'])->name('opd');
        Route::get('account/package', [AccountController::class, 'packageReport'])->name('package');
    });
});
