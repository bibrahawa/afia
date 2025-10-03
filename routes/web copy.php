<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    DashboardController, HospitalController, UserController,
    DepartmentController, ServiceController, EmployeeController,
    PatientController, AppointmentController, PackageController,
    TestController, ReportController, AccountController, AuthController,
    ProfileController, ConsultationController, MedicamentController,
    HospitalisationController, ChambreController, InsuranceClaimController, InsuranceCompanyController,
    InsuranceCoverageController, InvoiceItemController, InvoicesController,PatientInsuranceController, 
    PaymentController, InsuranceBalanceController, SmsController, SmsReportController
};

Route::get('new', function(){
    return view('consultations.facture.bilan_hormonale');
});


// Authentification
Route::get('/', [DashboardController::class, 'index'])->name('rdv');

Route::middleware('guest')->group(function () {
    Route::view('login', 'auth.login');
    Route::post('login', [AuthController::class, 'login'])->name('login');
});

Route::post('logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {

    // Profile
    // Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    // Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Dans routes/web.php
    Route::get('/sms/lists', [SmsController::class, 'smsLists'])->name('sms.lists');
    Route::get('/sms/send', [SmsController::class, 'newSms'])->name('sms.new');
    Route::post('/sms/send', [SmsController::class, 'send'])->name('sms.send');
    Route::post('/sms/send-bulk', [SmsController::class, 'sendBulk'])->name('sms.send-bulk');

    Route::get('/sms-report', [SmsReportController::class, 'index'])->name('admin.sms-report');
    Route::post('/sms-report/resend-failed', [SmsReportController::class, 'resendFailed']);

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
    Route::post('report/actes', [ReportController::class, 'rapportActes'])->name('reports.actes');
    Route::post('service/report', [ReportController::class, 'service'])->name('service.report');

    // Hospitalisations & chambres
    Route::resource('hospitalisations', HospitalisationController::class)->except('update');
    Route::put('hospitalisation/update', [HospitalisationController::class, 'update'])->name('hospitalisation.update');
    Route::get('hospitalisations/{hospitalisation}/payer', [HospitalisationController::class, 'payer'])->name('hospitalisations.payer');
    Route::get('hospitalisations/{hospitalisation}/facture', [HospitalisationController::class, 'facture'])->name('hospitalisations.facture');

    Route::resource('chambres', ChambreController::class)->except('update');
    Route::put('chambre/update', [ChambreController::class, 'update'])->name('chambre.update');

    // Gestion des assurances
    Route::prefix('insurance')->group(function () {
        Route::resource('insurance-companies', InsuranceCompanyController::class)->except(['create', 'edit', 'show', 'update', 'delete']);
        Route::put('insurance-companies/update', [InsuranceCompanyController::class, 'update'])->name('insurance-companies.update');
        Route::delete('insurance-companies/delete', [InsuranceCompanyController::class, 'destroy'])->name('insurance-companies.destroy');
        
        Route::resource('insurance-coverages', InsuranceCoverageController::class)->except(['create', 'edit', 'show', 'delete', 'update']);
        Route::put('insurance-coverages/update', [InsuranceCoverageController::class, 'update'])->name('insurance-coverages.update');
        Route::delete('insurance-coverages/delete', [InsuranceCoverageController::class, 'destroy'])->name('insurance-coverages.destroy');

        Route::resource('insurance_patient', PatientInsuranceController::class)->except(['create', 'edit', 'show', 'delete', 'update']);
        Route::put('insurance_patient/update', [PatientInsuranceController::class, 'update'])->name('insurance_patient.update');
        Route::delete('insurance_patient/delete', [PatientInsuranceController::class, 'destroy'])->name('insurance_patient.destroy');

        // Routes pour les factures
        Route::get('/invoice', [InvoicesController::class, 'index'])->name('invoice.index');
        Route::post('/invoice/add', [InvoicesController::class, 'store'])->name('invoice.add');
        Route::post('/invoice/update', [InvoicesController::class, 'update'])->name('invoice.update');
        Route::delete('/invoice/delete/{id}', [InvoicesController::class, 'destroy'])->name('invoice.delete');
        Route::get('/invoice/{id}/items', [InvoicesController::class, 'getItems']); // Pour AJAX

        // Routes pour les éléments de facture
        Route::get('/invoice/item', [InvoiceItemController::class, 'index'])->name('invoice.item.index');
        Route::post('/invoice/item/add', [InvoiceItemController::class, 'store'])->name('invoice.item.add');
        Route::post('/invoice/item/update', [InvoiceItemController::class, 'update'])->name('invoice.item.update');
        Route::delete('/invoice/item/delete/{id}', [InvoiceItemController::class, 'destroy'])->name('invoice.item.delete');

    });

    // Routes existantes pour les patients
    Route::resource('patient', PatientController::class);
    
    // Routes pour les paiements avec assurance
    Route::get('/payment/{patient}', [PaymentController::class, 'showPaymentPage'])->name('payment.show');
    Route::get('/payment/{patient}/{amount}/{assurance}', [PaymentController::class, 'calculateCoverage']);
    Route::post('/payment/process', [PaymentController::class, 'processPayment'])->name('account.payer');
    Route::post('/insurance/calculate', [PaymentController::class, 'calculateCoverage'])->name('insurance.calculate');
    Route::get('/hospitalisation/{hospitalisation}/paiement', [PaymentController::class, 'paiementHospitalisation'])->name('hospitalisation.paiement');
    
    // Routes pour la gestion des assurances
    Route::resource('insurance-claims', InsuranceClaimController::class);
    
    // Routes pour les factures
    Route::resource('invoices', InvoiceController::class);
    Route::get('/invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');


    Route::prefix('insurance/balances')->name('insurance.balances.')->group(function() {
    
        // Liste des soldes des assurances
        Route::get('/', [InsuranceBalanceController::class, 'index'])
            ->name('index');
        
        // Détails d'une assurance spécifique
        Route::get('/{insurance}', [InsuranceBalanceController::class, 'show'])
            ->name('show');
        
        // Paiement individuel d'une facture
        // Route::post('/payment/{invoice}', [InsuranceBalanceController::class, 'processPayment'])
        //     ->name('payment');
        
        // Paiement groupé
        Route::post('/paiement', [InsuranceBalanceController::class, 'ProcessPaiement'])->name("payment");
        
        // Export CSV
        Route::get('/export/csv', [InsuranceBalanceController::class, 'export'])
            ->name('export');
            
    });

    Route::get('/rapport-clinique', [RapportCliniqueController::class, 'index'])->name('rapport-clinique.index');

});

